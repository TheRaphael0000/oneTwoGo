<?php

namespace App\Http\Controllers;

use DB;
use App\Vote;
use App\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Session;
use Auth;
use App\Theme;

class StoryController extends Controller
{
    static $minCharsTitle = 0;
    static $maxCharsTitle = 30;
    static $minCharsStory = 280;
    static $maxCharsStory = 2500; // basic sizeof A4 page in Microsoft Word

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function topPage()
    {
        // get 10 top stories
        $stories = Story::all();
        $evalutedStories = [];
        foreach($stories as $story)
        {
            $votes = $story->votes;
            $up = $votes->where('vote', '=',  '1')->count();
            $dn = $votes->where('vote', '=', '-1')->count();

            $commentaries = $story->commentaries();
            $cc = $commentaries->count();

            //echo "id:".$story->id."_up:".$up."_dn:".$dn."_cc:".$cc;

            $evalutedStories[$story->getId()] = ((2 * $up) - (3 * $dn) + (5 * $cc));
        }

        // Sort by score
        arsort($evalutedStories);

        // Get top 10 keys
        $keys = array_slice(array_keys($evalutedStories), 0, 10);

        // Implode keys
        $implodedKeys = implode(',', $keys);

        // Fetch top stories
        $storiesPaged = Story::whereIn('id', $keys)->orderByRaw(DB::raw("FIELD(id, $implodedKeys)"))->paginate(3);

        return $this->paged($storiesPaged);
    }

    public function freshPage()
    {
        $storiesPaged = Story::orderBy('created_at', 'DESC')->paginate(3);
        return $this->paged($storiesPaged);
    }

    public function randomPage()
    {
        $seed = Session::get("randomPageSeed"); //if unset -> null
        $storiesPaged = Story::inRandomOrder($seed)->paginate(3); //if seed == null -> like no seed
        return $this->paged($storiesPaged);
    }

    public function byUser($id)
    {
        return view("story.paged")->with("routeAJAX", route("stories.byUserPage", ['id' => $id]));
    }

    public function byUserPage($id)
    {
        $storiesPaged = Story::where('user_id', $id)->paginate(3);
        return $this->paged($storiesPaged);
    }

    public function byTheme($id)
    {
        return view("story.paged")->with("routeAJAX", route("stories.byThemePage", ['id' => $id]));
    }

    public function byThemePage($id)
    {
        $storiesPaged = Story::where('theme_id', $id)->paginate(3);
        return $this->paged($storiesPaged);
    }

    public function byId($id)
    {
        return view("story.paged")->with("routeAJAX", route("stories.byIdPage", ['id' => $id]));
    }

    public function byIdPage($id)
    {
        $storiesPaged = Story::where('id', $id)->paginate(1);
        return $this->paged($storiesPaged);
    }

    private function paged($stories)
    {
        $output = "";

        if(count($stories) <= 0)
            abort(403, 'Unauthorized action.');

        foreach ($stories as $story)
        {
            $output .= view("story.show", ['story'=> $story]);
        }
        return $output;
    }

    public function random()
    {
        Session::put("randomPageSeed", rand()); //every time this route is used get a new random seed
        return view("story.paged")->with("routeAJAX", route("stories.randomPage"));
    }

    public function fresh()
    {
        return view("story.paged")->with("routeAJAX", route("stories.freshPage"));
    }

    public function top()
    {
        return view("story.paged")->with("routeAJAX", route("stories.topPage"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $themes = Theme::where('active', 1)->get();;
        $page = view('story/create', ['themes' => $themes, 'lenghtConstraints' => [self::$minCharsTitle, self::$maxCharsTitle, self::$minCharsStory, self::$maxCharsStory]]);
        return $page;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
          'title' => 'required',
          'text' => 'required',
        ]);

        $constraintsList = Session::get('constraints');
        $themeid = Session::get('theme')->id;

        //Same verification algorithme as in the view
        $isValid = $this->verify($constraintsList, $request['text']);
        $nbCharsTitle = strlen($request['text']);
        $isValid &= ($nbCharsTitle < self::$minCharsTitle || $nbCharsTitle > self::$maxCharsTitle);
        if($isValid)
        {
            $story = new Story([
            'title' => $request['title'],
            'text' => nl2br($request['text']),
            'user_id' => Auth::user()->getId(),
            'theme_id' => $themeid,
            'deleteVoted' => 0,
            ]);

            $story->save();

            $story->constraints()->saveMany($constraintsList);

            return redirect()->route('stories.fresh')->with('success', 'Story created successfully.');
        }
        else
        {
            return redirect()->route('stories.fresh')->with('failure', 'Story couldn\'t be added.');
        }
    }

    public function verify($constraints, $text)
    {
        $nbChars = strlen($text);
        if($nbChars < self::$minCharsStory || $nbChars > self::$maxCharsStory)
            return false;

        foreach($constraints as $constraint){

            $regex = "/".$constraint['word']."/i";
            preg_match_all($regex, $text, $matches);
            $count = sizeof($matches[0]);
            if($constraint['use'] == 1 && $count <= 0 || $constraint['use'] == 0 && $count > 0)
                return false;
        }
        return true;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Story  $project
     * @return \Illuminate\Http\Response
     */
    public function access($id)
    {
        $story = Story::firstOrFail($id);
        return $this->show($story);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function edit(Story $story)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Story $story)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function destroy(Story $story)
    {

    }

    public function like($id)
    {
      $story = Story::findOrFail($id);
      return $story->like();
    }

    public function dislike($id)
    {
      $story = Story::findOrFail($id);
      return $story->dislike();
    }
}
