<?php
require __DIR__.'/bootstrap/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// FORCE CREATE TABLE
try {
    echo "Attempting to create 'presencia_usuarios' table...\n";
    if (!\Illuminate\Support\Facades\Schema::hasTable('presencia_usuarios')) {
        \Illuminate\Support\Facades\Schema::create('presencia_usuarios', function ($table) {
            $table->increments('id_presencia');
            $table->integer('id_usuario');
            $table->string('ubicacion')->nullable();
            $table->dateTime('last_seen')->nullable();
            $table->timestamps();
        });
        echo "SUCCESS: Table 'presencia_usuarios' created.\n";
    } else {
        echo "INFO: Table 'presencia_usuarios' already exists.\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
