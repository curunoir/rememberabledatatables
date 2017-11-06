# rememberabledatatables
Custom EloquentEngine witch caching abilities for https://github.com/yajra/laravel-datatables

Inspired by https://github.com/dwightwatson/rememberable


In config/datatables.php :

    'engines' => [
        'eloquent'   => App\Datatables\RememberableEloquentEngine::class
    ],
    
    Usage :
    
     $model = User::select('*');

     $data = Datatables::of($model)->remember(5)->cacheTags('users');
     return $data->make();
