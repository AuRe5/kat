<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';
#require_once(__DIR__.'/../lib/fpdf17/fpdf.php');
#require_once(__DIR__.'/../lib/FPDI-1.4.4/fpdi.php');


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
    
    # Check
    # if abt = SA | CH -> keine Rechnung
    # if tn = extern -> keine Rechnung
    # AuRe vs. Korps Konto
    
    
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
            'kurskosten' => $query['kurskosten'],
            'grusszeile' => $query['grusszeile'],
            'konto' => 'IBAN: CHxyz',
        )));
});

$app->get('/readconfirmation/{hash}', function ($hash) use ($app) {
    
    $sql = 'SELECT * FROM anmeldungen a JOIN kurse k ON a.kursnr = k.kursnr  WHERE a.hash = ?';
    $query = $app['db']->fetchAssoc($sql, array((string) $hash));
    
    return nl2br($app['twig']->render('readconfirmation.twig', array(
        'pfadiname' => $query['pfadiname'],
        'geschlecht' => $query['geschlecht'],
        'grusszeile' => $query['grusszeile'],
    )));
});

$app->get('/jsrequest/{hash}', function ($hash) use ($app) {
    
    $sql = 'SELECT * FROM anmeldungen a JOIN kurse k ON a.kursnr = k.kursnr  WHERE a.hash = ?';
    $query = $app['db']->fetchAssoc($sql, array((string) $hash));
    

    ###
    
    // initiate FPDI 
    $pdf =& new FPDI(); 
    // add a page 
    $pdf->AddPage(); 
    // set the sourcefile 
    $pdf->setSourceFile('.data/bestaetigungjugendurlaubd.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    // use the imported page as the template 
    $pdf->useTemplate($tplIdx, 0, 0); 

    // now write some text above the imported page 
    $pdf->SetFont('Arial'); 
    $pdf->SetTextColor(0,10,100); 
    $pdf->SetFontSize(10);

    $pdf->SetXY(57, 34); 
    $pdf->Write(0, utf8_decode($query['nachname']." ".$query['vorname'])); 

    $pdf->SetXY(48, 44); 
    $pdf->Write(0, utf8_decode($query['geburtstag']));

    $pdf->SetXY(39, 54.5); 
    $pdf->Write(0, utf8_decode($query['strasse'].", ".$query['plz']." ".$query['ort']));  

    $pdf->SetXY(32, 80); 
    $pdf->Write(0, "20.04.2014");

    $pdf->SetXY(112, 80); 
    $pdf->Write(0, "24.04.2014");

    $pdf->SetXY(23, 165); 
    $pdf->Write(0, "X");

    $pdf->SetXY(23, 186); 
    $pdf->Write(0, utf8_decode("Teilnehmer im J+S Leiterkurs LS/T, Kursnummer: ".$query['kursnr']));

    $pdf->SetXY(23, 194); 
    $pdf->Write(0, utf8_decode("Organisiert von der Ausbildungsregion 5, Pfadi Züri, Pfadibewegung Schweiz in Zusammenarbeit"));

    $pdf->SetXY(23, 202); 
    $pdf->Write(0, "mit Jugend + Sport.");

    $pdf->SetXY(46, 267); 
    $pdf->Write(0, utf8_decode("Kontakt für Rückfragen: Simon Stäheli v/o Goblin, Waffenplatzstr. 40, 8002 Zürich"));

    $pdf->SetXY(46, 271); 
    $pdf->Write(0, utf8_decode("goblin@pfadi-af.ch, Ausbildungsverantwortlicher, Ausbildungsregion 5, Pfadi Züri"));

    #echo "pdf done";

    ###


    return $pdf->Output('jsbestaetigung.pdf', 'D');

});

$app->run();

?>
