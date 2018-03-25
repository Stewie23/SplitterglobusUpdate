<?
function isOffical($name)
{
    $temp = array_keys(SemanticwikiApiQuery(urlencode($name), 'Offiziell::Ja')["query"]["results"])[0];
    //If the result of this query is empty, its not offical
    if ($temp != "")
    {
        return True;
    }
    else 
    {
        return False;
    }
}
function RemoveWikiLinks($string)
{    
    return str_replace(array("[[","]]"),"",$string);
}
function createWikiLink($string)
{
    return '<a href="http://www.splitterwiki.de/wiki/'.$string.'">'.$string.'</a>';
}
function MediawikiApiQuery($arg1)
{
    $url = 'http://www.splitterwiki.de/w/api.php?action=query&titles='.$arg1.'&prop=revisions&rvprop=content&format=json&formatversion=2';
    $ch = curl_init($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function SemanticwikiApiQuery($arg1, $arg2)
{
    $url = 'http://www.splitterwiki.de/w/api.php?action=ask&query=[['.$arg1.']]%20[['.$arg2.']]|sort%3DModification%20date|order%3Ddesc&format=json';
    $ch = curl_init($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $SemanticContent = curl_exec($ch);
    curl_close($ch);
    $parsed_json = json_decode($SemanticContent,true);
    return $parsed_json;
}
//arguments coming from the google earth client
$id= trim(htmlspecialchars($_GET["id"]));
$target = trim(htmlspecialchars($_GET["target"]));
//First API Query for mediawiki stuff
$content = MediawikiApiQuery($id);
//Second API Query this time semantic wiki, for szenarien
//Store Szenarien as Array
$szenario = (array_keys(SemanticwikiApiQuery('Handlungsorte::'.$id,'Kategorie:Szenario')["query"]["results"]));
//third API Query this time semantic wiki, for abenteuer
//Store Abenteuer as Array
$abenteuer =(array_keys(SemanticwikiApiQuery('Handlungsorte::'.$id,'Kategorie:Abenteuer')["query"]["results"]));
//Check if abenteuer are Offiziel or Not
//http://www.splitterwiki.de/w/api.php?action=ask&query=[[Abenteuername]][[Offiziell::Ja]]|sort%3DModification%20date|order%3Ddesc&format=json
//loop trough abenteuer
for ($i = 0; $i < count($abenteuer); $i++) {
    $name = urlencode($abenteuer[$i]);
    $temp = array_keys(SemanticwikiApiQuery($name, 'Offiziell::Ja')["query"]["results"])[0];
    //If the result of this query is empty, its not offical
    if ($temp != "")
    {
        //echo $abenteuer[$i];
        //echo " ist Offiziel<br>";
    }
}
//Somestring operations with the results from the first api query
$teile = explode ("}" ,$content);
$content = $teile[0];
//Remove parts before Vorlage
$teile = explode ("{{" ,$content);
$content = $teile[1];
//break after new line
$teile = explode("|",$content);
//Look for information in the Vorlage, for now echo them out
//In the Future store them for putting them into the kml update
for ($i = 0; $i < count($teile); $i++) {
    $temp = explode ("=",$teile[$i])[0];
    if  (strcmp (utf8_encode("BevölkerungAnzahl") ,$temp) == 0){
        $var = explode ("=",$teile[$i])[1];
        //remove \n
        $BevAnzahl = "Bevoelkerung: ";
        $BevAnzahl .= substr($var,0,-2);
        $BevAnzahl .= "<br>";
    }
    if (strcmp (utf8_encode("BevölkerungText") ,$temp) == 0){
        $var = explode ("=",$teile[$i])[1];
        //remove \n
        //$BevVerteilung = "Verteilung: ";
        $BevVerteilung .=  substr($var,0,-2);
        $BevVerteilung .= "<br>";
        $BevVerteilung = RemoveWikiLinks($BevVerteilung);
    }
}
if (count($abenteuer) != 0)
{
$AusgabeAbenteuer ="<br><b>Abenteuer:</b><br>";
}
for ($i = 0; $i < count($abenteuer); $i++) 
    {
    $AusgabeAbenteuer .= createWikiLink($abenteuer[$i]);
    if (isOffical($abenteuer[$i]) == True)
        {
        $AusgabeAbenteuer .= " (Offiziell)";
        }
    else 
        {
        $AusgabeAbenteuer .= " (Inoffiziell)";
        }
    $AusgabeAbenteuer .= "<br>";
    }

if (count($szenario) != 0)
    {
        $AusgabeSzenario ="<br><b>Szenario:</b><br>";
    }
for ($i = 0; $i < count($szenario); $i++) 
    {
        $AusgabeSzenario .= createWikiLink($szenario[$i]);
        if (isOffical($szenario[$i]) == True)
        {
            $AusgabeSzenario .= " (Offiziell)";
        }
        else
        {
            $AusgabeSzenario .= " (Inoffiziell)";
        }
        $AusgabeSzenario .= "<br>";
}
//Output
header('Content-type: application/vnd.google-earth.kml+xml');
header('Content-Disposition: attachment; filename="temp.kml"');

echo "<";
?>?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<NetworkLinkControl>
  <Update>
    <targetHref><?=$target?></targetHref>
    <Change>
    <Placemark targetId="<?=$id?>">
        <description><![CDATA[
        <?=$BevAnzahl?>
        <?=$BevVerteilung?>
        <?=$AusgabeAbenteuer?>
        <?=$AusgabeSzenario?>
		]]></description>
    </Placemark>
   </Change>
  </Update>
</NetworkLinkControl>
</kml>