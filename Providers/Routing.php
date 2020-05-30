<?php
namespace Arcaela\Providers;
use Illuminate\Support\Facades\Route;

class Routing extends \Illuminate\Foundation\Support\Providers\RouteServiceProvider{
    protected $namespace = 'App\Http\Controllers';
    protected $defaults = [
        'middleware' => [
            'web' => [
                'web',
            ],
            'api' => [
                //'csrf',
                'api',
            ],
        ],
    ];
    public function boot() { parent::boot(); }
    public function map() {
        $this->mapDomainsRoutes();
        $this->mapSubDomainsRoutes();
        $this->routingAssetsResources();
    }
    
    public function getMiddleware($guest='web'){return $this->defaults['middleware'][$guest]??$this->defaults['middleware']['web'];}
    function mapDomainsRoutes(){
        $files = glob(base_path('routes/domains/*'));
        foreach($files as $file){
            $subdomain = pathinfo($file, PATHINFO_FILENAME);
            Route::domain($subdomain)
                ->namespace($this->namespace)
                ->middleware($this->getMiddleware($subdomain))
                ->group($file);
        }
    }
    function mapSubDomainsRoutes(){
        $files = glob(base_path('routes/subdomains/*'));
        foreach($files as $file){
            $subdomain = pathinfo($file, PATHINFO_FILENAME);
            Route::domain(\Arcaela\SiteHost::subdomain($subdomain,false))
                ->namespace($this->namespace)
                ->middleware($this->getMiddleware($subdomain))
                ->group($file);
        }
    }

    public function routingAssetsResources(){
        Route::get('{lib}/{fullName}',function($lib,$FullName){
            $FullPath = "$lib/$FullName";
            $info = pathinfo($FullPath);
            $paths = [];
            $options = [
                'public'=>public_path(),
                'resource'=>resource_path(),
            ];
            foreach($options as $key => $path){
                $paths = array_merge($paths, [
                    "$path/$FullPath",
                    "$path/$FullPath.$lib",
                ],($key=='resource'?[
                    "$path/{$info['dirname']}/{$info['filename']}.blade.php",
                    "$path/$FullPath.blade.php",
                ]:[]));
            }
            if($lib=='images') $paths = array_merge($paths,[
                storage_path($FullPath),
                storage_path("app".strchr($FullPath,'/')),
            ]);
            foreach($paths as $path)
                if(file_exists($path)){
                    if(preg_match("/^.*\.blade\.php$/",$path)){
                        view()->addNamespace('ArcaelaResoureRouting', $options['resource']);
                        return view('ArcaelaResoureRouting::'.str_replace('/','.',preg_replace("/^.*resources\/(.*)\.blade\.php$/","$1",$path)));
                    }
                    else
                        return response(file_get_contents($path))->header('Content-type',\Arcaela\MimeType::fromExtension($lib));
                }
            abort(404);
        })->where(['lib'=>'js|json|css|sass|scss|vue|images|fonts','fullName'=>'.*']);
    }    
}
