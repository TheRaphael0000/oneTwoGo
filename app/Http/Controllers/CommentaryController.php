<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Commentary;
use Illuminate\Http\Request;
use Auth;

class CommentaryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        'story_id' => 'required|integer',
        'comment' => 'required',
      ]);

      $comment = new Commentary([
        'story_id' => $request->get('story_id'),
        'user_id' => Auth::user()->id,
        'comment' => $request->get('comment'),
        'created_at' => Carbon::now(),
      ]);

      $comment->save();
      return view('commentary.show', ['commentary' => $comment]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Commentary  $commentary
     * @return \Illuminate\Http\Response
     */
    public function show(Commentary $commentary)
    {
        return view('commentary.show', ['commentary' => $commentary]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Commentary  $commentary
     * @return \Illuminate\Http\Response
     */
    public function edit(Commentary $commentary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Commentary  $commentary
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Commentary $commentary)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Commentary  $commentary
     * @return \Illuminate\Http\Response
     */
    public function destroy(Commentary $commentary)
    {
        //
    }
}
