<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupActionQueue;

class GroupActionQueueController extends Controller
{
    public function get_queue(){
        $queue = GroupActionQueue::with('identity')->get();
        return $queue;
    }

}
