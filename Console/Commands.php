<?php
Artisan::command('arcaela:optimize', function () {
    $this->line("Optimizando...");
    $this->error("(".count(array_filter([
        ...glob(base_path('/bootstrap/cache/*.*')),
    ], function($path){
        try { return !unlink($path); }
        catch (\Throwable $th) { return false; }
    })).") archivos eliminados.");
    Artisan::call("cache:clear");
    Artisan::call("config:clear");
    Artisan::call("event:clear");
    Artisan::call("route:clear");
    Artisan::call("view:clear");
    Artisan::call("clear-compiled");
    Artisan::call("optimize:clear");
    $command = \Arcaela\Console::run('composer dump-autoload');
    $this->info($command);
})
->describe('Borrar los archivos compilados por el Framework');

Artisan::command('arcaela:extendsable {ClassName? : Nombre de la clase para extender}',function($ClassName=null){
    if(!$ClassName)
        $this->error("try run: php artisan arcaela:extendsable {ClassName}");
    else {
        $template = __DIR__."/Components/Extendsable.template.php";
        if(file_exists($template)){
            $dir = app_path('/Extendsable/');
            $pathFull = $dir.$ClassName.'.php';
            $i=1;
            while (file_exists($pathFull)) {
                $pathFull = $dir.$ClassName."-{$i}.php";
                $i++;
            }
            $this->line("Se creara el archivo en {$pathFull}");
            $this->line("Construyendo {$ClassName}");
            File::makeDirectory($dir, 0755, true, true);
            File::put($pathFull,str_replace([
                "ExtendsableClassName",
            ],[
                $ClassName,
            ],file_get_contents($template)));
            $this->info("Se ha creado con exito el objeto extensible");
        }
        else
            $this->line("No hemos conseguido la plantilla para el objeto");
    }
})->describe('Construir clase extensible con funciones llamadas de manera estatica y directa en una clase.');


Artisan::command('arcaela:check',function(){
    $this->line("Ejecutando...");
    $t=false;
    foreach(['host','schema','port'] as $k)
        $t=(array_key_exists($k,config('broadcasting.connections.pusher.options'))||$t);
    $this->info('El servidor '.($t?'':'no ').'puede funcionar como WebSocketServer');
    $this->error("Finalizado.");
})->describe('Verificar que todas las instancias de arcaela están funcionando correctamente.');


Artisan::command('arcaela:websocket {action?}',function($action=null){
    switch(strtolower($action)){
        case 'install':
            $this->info("Instalando...");
            if(!File::exists(base_path('config/websockets.php')) || $this->confirm("¿Reemplazar archivo de configuracion?")){
                if(File::copy(__DIR__.'/Components/websockets.php',base_path('config/websockets.php')))
                    $this->line("websockets.php copiado!");
                else $this->error("¡Error al copiar 'websockets.php'!");
            }
            $this->info("Configurado.");
        break;
        case 'start':
            $this->info("¡WebSocket Server!");
            Artisan::call(\BeyondCode\LaravelWebSockets\Console\StartWebSocketServer::class);
        break;
        case 'help':
            foreach(['migrate','config','start'] as $c)
                $this->line('php artisan arcaela:websocket '.$c);
        break;
    }
})
->describe('Modulo de WebSocket para Laravel');

// Artisan::command('',function(){})->describe();
