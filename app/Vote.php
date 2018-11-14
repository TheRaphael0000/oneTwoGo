<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
  public const UPVOTE = 1;
  public const DOWNVOTE = -1;

  public function getStory()
  {
      return DB::table('stories')->where('id', $this->story_id)->get()->first();
  }

  public function getOwner()
  {
      return DB::table('users')->where('id', $this->user_id)->get()->first();
  }

  public function getId()
  {
      return $this->id;
  }
}
