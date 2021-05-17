# laravel-snowflake

### 安装 

    composer require ufucms/laravel-snowflake:"dev-master"


### 生成配置 

    php artisan vendor:publish --provider="Ufucms\LaravelSnowflake\Providers\SnowflakeServiceProvider"


### 引入 

    use Ufucms\LaravelSnowflake\Snowflake;
    
    $sn = new Snowflake();
    $id = $sn->nextId();
    
   