<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;

date_default_timezone_set('Europe/Zurich');

$app = new Silex\Application();
$app['debug'] = true;

# REGISTERS

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$db_config = json_decode(file_get_contents(__DIR__.'/../src/config/db.json'), true);
//TODO TW: Muss das ganze Config management (Hostunabhängig machen. 

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
        'db.options' => array(
        'driver' => 'pdo_mysql',
        'dbname' => $db_config['database'],
        'host'   => $db_config['host'],
        'user'   => $db_config['username'],
        'password' => $db_config['password'],
        'charset' => 'utf8',
        'port' => '3306',
    ),
));

$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SwiftmailerServiceProvider());

# ROUTES ###############################################

$app->get('/', function () use ($app) {
    return "empty site";
});

$app->get('/confirmation', function () use ($app) {
    return "hash missing";
});

$app->get('/participant/{hash}', function ($hash) use ($app) {
    
    $sql = 'SELECT * FROM anmeldungen WHERE hash = ?';
    $query = $app['db']->fetchAssoc($sql, array((string) $hash));
    
    $output = "";
        foreach($query as $key => $value) {
            $output .= "$key - $value<br>";
        }
    
    //print "START".$output."END";
    /*
    return $app['twig']->render('annualplan.twig', array(
        'query' => $query,
    ));
    */
    return $output;
});

$app->get('/confirmation/{hash}', function ($hash) use ($app) {
    
    $sql = 'SELECT * FROM anmeldungen a JOIN kurse k ON a.kursnr = k.kursnr  WHERE a.hash = ?';
    $query = $app['db']->fetchAssoc($sql, array((string) $hash));
    
    return nl2br($app['twig']->render('confirmation.twig', array(
            'pfadiname' => $query['pfadiname'],
            'geschlecht' => $query['geschlecht'],
            'kursnr' => $query['kursnr'],
            'kurs' => $query['kurs'],
            'kursleitername' => $query['kursleitername'],
            'kursleiterpfadiname' => $query['kursleiterpfadiname'],
            'kursleitergeschlecht' => $query['kursleitergeschlecht'],
            'kursleiteremail' => $query['kursleiteremail'],
            'kursleiternatel' => $query['kursleiternatel'],
            'kursdaten' => $query['kursdaten'],
            'konto' => 'IBAN: CHxyz',
        )));
});

$app->run();

?>
