<?php
namespace Arcaela\Helper;
use Illuminate\Support\Str;
use \Illuminate\Queue\SerializesModels;
use \Illuminate\Foundation\Events\Dispatchable;
use \Illuminate\Broadcasting\InteractsWithSockets;
use \Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class Event implements ShouldBroadcast {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public static $info;
    public function __construct(array $info) { static::$info = $info; }
    public function broadcastOn() {
        return Str::kebab(static::$info['channel']);
    }
    public function broadcastAs() {
        return static::$info['namespace'].static::$info['event'];
    }
    public function broadcastWith() {
        return static::$info['data'];
    }
}