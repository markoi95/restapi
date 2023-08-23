<?php

require 'flight/Flight.php';
require 'jsonindent.php';

Flight::register('db', 'Database', array('rest')); //registrujemo promenljivu db, koja ja objekat klase Database, čiji je parametar za konstruktor naziv baze a to je rest
$json_podaci = file_get_contents("php://input");
Flight::set('json_podaci', $json_podaci);

Flight::route('/', function () {
    echo 'hello world!';
});



Flight::route('GET /novosti', function(){
    header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db(); // isntacira konekciju sa bazom
	$db->select();
	$niz=array();
	while ($red=$db->getResult()->fetch_object()){
		$niz[] = $red;
	}
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($niz,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;
});

Flight::route('GET /novosti/@id', function($id){
    header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select("novosti", "*", "kategorije", "kategorija_id", "id", "novosti.id = ".$id, null);
	$red=$db->getResult()->fetch_object();
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($red,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;

});

Flight::route('POST /novosti', function(){
    header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
    $podaci_json = Flight::get("json_podaci");
    $podaci = json_decode ($podaci_json);
    if ($podaci == null){
        $odgovor["poruka"] = "Niste prosledili podatke";
        $json_odgovor = json_encode ($odgovor);
        echo $json_odgovor;
        return $json_odgovor;
        } else {
            if (!property_exists($podaci,'naslov')||!property_exists($podaci,'tekst')||!property_exists($podaci,'kategorija_id')){// da li u okviru podataka postoji naslov kao kljuc ili tekst kao kljuc ili kat_id kao kljuc
                    $odgovor["poruka"] = "Niste prosledili korektne podatke"; // ako bilo koji ne postoji kreira se odgovor koji sadrzi poruku
                    $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                    echo $json_odgovor;
            
            } else {
                    $podaci_query = array();
                    foreach ($podaci as $k=>$v){ // kljuc vrednost
                        $v = "'".$v."'"; //pod navodnike da bi values u sql upitu bilo prosledjeno kako treba
                        $podaci_query[$k] = $v; //za kljuc k se dodaje vrednost v
                    }
                    if ($db->insert("novosti", "naslov, tekst, kategorija_id, datumvreme", array($podaci_query["naslov"], $podaci_query["tekst"], $podaci_query["kategorija_id"], 'NOW()'))){//now funkcija u sql
                        $odgovor["poruka"] = "Novost je uspešno ubačena";
                        $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                        echo $json_odgovor;
                    } else {
                        $odgovor["poruka"] = "Došlo je do greške pri ubacivanju novosti";
                        $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                        echo $json_odgovor;
                    }
            }
            }	

});

Flight::route('PUT /novosti/@id', function($id){
    header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
    $podaci_json = Flight::get("json_podaci");
    $podaci = json_decode ($podaci_json);
    if ($podaci == null){
        $odgovor["poruka"] = "Niste prosledili podatke";
        $json_odgovor = json_encode ($odgovor);
        echo $json_odgovor;
        return $json_odgovor;
        } else {
            if (!property_exists($podaci,'naslov')||!property_exists($podaci,'tekst')||!property_exists($podaci,'kategorija_id')){// da li u okviru podataka postoji naslov kao kljuc ili tekst kao kljuc ili kat_id kao kljuc
                    $odgovor["poruka"] = "Niste prosledili korektne podatke"; // ako bilo koji ne postoji kreira se odgovor koji sadrzi poruku
                    $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                    echo $json_odgovor;
            
            } else {
                    $podaci_query = array();
                    foreach ($podaci as $k=>$v){ // kljuc vrednost
                        $v = "'".$v."'"; //pod navodnike da bi values u sql upitu bilo prosledjeno kako treba
                        $podaci_query[$k] = $v; //za kljuc k se dodaje vrednost v
                    }
                    $kljucevi = array('naslov','tekst','kategorija_id');
                    $vrednosti = array($podaci->naslov, $podaci->tekst, $podaci->kategorija_id);
                    if ($db->update("novosti", $id, $kljucevi, $vrednosti)){//now funkcija u sql za vreme
                        $odgovor["poruka"] = "Novost je uspešno izmenjena";
                        $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                        echo $json_odgovor;
                    } else {
                        $odgovor["poruka"] = "Došlo je do greške pri menjanju novosti";
                        $json_odgovor = json_encode ($odgovor,JSON_UNESCAPED_UNICODE);
                        echo $json_odgovor;
                    }
            }
            }	
});

Flight::route('DELETE /novosti/@id', function(){

});

Flight::route('GET /kategorije', function(){
    header ("Content-Type: application/json; charset=utf-8");
	$db = Flight::db();
	$db->select("kategorije","*",null,null,null,null,null); //tabela kategorije, rows = * tj svi, join_table & join_key1 & join_key2 & where & order = null (svi su null)
	$niz=array();
    $pom = 0;
	while ($red=$db->getResult()->fetch_object()){
		$niz[$pom]["id"] = $red->id;
        $niz[$pom]["kategorija"] = $red->kategorija;
        $db_pom = new Database("rest");
        $db_pom->select("novosti", "*", null, null, null, "novosti.kategorija_id = ".$red->id, null);
        //iz novosti daju sve novosti i njihove informacije tamo gde su novosti.katgeorija_id jednaki red->id
        while($red_pom=$db_pom->getResult()->fetch_object())
        {
            $niz[$pom]["novosti"][] = $red_pom;
        }
        $pom++;
	}
	//JSON_UNESCAPED_UNICODE parametar je uveden u PHP verziji 5.4
	//Omogućava Unicode enkodiranje JSON fajla
	//Bez ovog parametra, vrši se escape Unicode karaktera
	//Na primer, slovo č će biti \u010
	$json_niz = json_encode ($niz,JSON_UNESCAPED_UNICODE);
	echo indent($json_niz);
	return false;
});

Flight::route('GET /kategorije/@id', function(){

});

Flight::route('POST /kategorije', function(){

});

Flight::route('PUT /kategorije/@id', function(){

});

Flight::route('DELETE /kategorije/@id', function(){

});

Flight::start();