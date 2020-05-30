<?php
namespace Arcaela;
use Arcaela\Closure,
    Illuminate\Support\Str;
class WebSocket extends Macroable{
    protected $constants = [
        'namespace'=>'App\Events',
        'event'=>'WebSocketArcaelaRunTimer',
        'channel'=>'own',
        'data'=>[],
    ];
    public function lib(){
        return [
            "const"=>function(...$v){
                return blank($v)?$this->constants:(
                    (count($v)==1)?(
                        is_array($v[0])?(collect($this->constants)->only($v[0])):($this->constants[$v[0]]??null)
                    ):((count($v)>1)?(function()use($v){$this->constants[$v[0]]=$v[1];return$this;})():null)
                );
            },
            "channel"=>function(...$name){ return blank(...$name)?$this->constants['channel']: $this->const('channel',new \Illuminate\Broadcasting\Channel($name[0])); },
            "private"=>function(...$name){ return blank(...$name)?$this->constants['channel']: $this->const('channel',new \Illuminate\Broadcasting\PrivateChannel($name[0])); },
            "presence"=>function(...$name){ return blank(...$name)?$this->constants['channel']: $this->const('channel',new \Illuminate\Broadcasting\PresenceChannel($name[0])); },
            "namespace"=>function(...$namespace){ return $this->const('namespace',...$namespace); },
            "event"=>function(...$event){ return $this->const('event',...$event); },
            "data"=>function(...$data){ return $this->const('data',...$data); },
            "make"=>function(array $params){
                foreach($params as $key => $value){
                    if(array_key_exists($key, [
                        'channel',
                        'private',
                        'presence',
                        'namespace',
                        'event',
                        'data',
                    ])) $this->$key($value);
                }
                return (new class ($params) implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow{
                    use \Illuminate\Foundation\Events\Dispatchable,
                        \Illuminate\Broadcasting\InteractsWithSockets,
                        \Illuminate\Queue\SerializesModels;
                    public function __construct(array $info=[]) { $this->info=$info; }
                    public function broadcastOn() { return $this->info["channel"]; }
                    public function broadcastAs() { return Str::finish(str_replace(".","\\",$this->info["namespace"]),"\\").$this->info["event"]; }
                    public function broadcastWith() { return $this->info["data"]; }
                    public function __toString() { return collect($this->info)->toJson(); }
                });
            },
            "send"=>function(){ return event(static::make($this->const())); },
            "broadcast"=>function(){ return broadcast(static::make($this->const())); },
            "auth"=>function(...$params){
                $route = $params[0]&&!preg_match("/\\/",$params[0])?$params[0]:'/broadcasting/auth';
                $callback = $params[1]?(function(\Illuminate\Http\Request $request)use($params){
                    if ($request->hasSession()) $request->session()->reflash();
                    return $callback(Broadcast::auth($request));
                }):\Illuminate\Broadcasting\BroadcastController::class.'@authenticate';
                \Illuminate\Support\Facades\Route::match(['get', 'post'], $route, $callback);
            },
        ];
    }
};
