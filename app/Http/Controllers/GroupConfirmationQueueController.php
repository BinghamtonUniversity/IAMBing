<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupConfirmationQueue;

class GroupConfirmationQueueController extends Controller
{
    public function get_queue(){
        $queue = GroupConfirmationQueue::with('identity')->get();
        return $queue;
    }

}
