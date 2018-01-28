#!/usr/bin/php
<?PHP

function xml_encode($string) {
  return htmlspecialchars($string, ENT_XML1, 'UTF-8');
}

$allTemplates = json_decode(file_get_contents("/tmp/community.applications/tempFiles/templates.json"),true);
$userTemplates = glob("/boot/config/plugins/dockerMan/templates-user/*.xml");

if (! $allTemplates ) {
  echo "Before running this script, you MUST go to the Apps Tab First!";
  exit();
}

foreach ($userTemplates as $user) {
  $xml = simplexml_load_file($user);
  $repository = explode(":",$xml->Repository);
  $repo = $repository[0];
  
  echo "$user - Searching for $repo";
  $flag = false;
  foreach ($allTemplates as $template) {
    if ( $template['Blacklist'] ) { continue; }
    if ( $template['Plugin'] ) { continue; }
    $testRepo = explode(":",$template['Repository']);
    if ( $testRepo[0] != $repo ) { continue; }
    echo "    found {$testRepo[0]}";
    $support = $template['Support']; $project = $template['Project'];
    echo "\nUpdating template\nSupport: $support\nProject: $project\n";
    $xml->Support = xml_encode($support);
    $xml->Project = xml_encode($project);
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    if ( ! is_file("$user.bak") ) {
      copy($user,"$user.bak");
    }
    file_put_contents($user, $dom->saveXML());    
    $flag = true;
    break;
  }
  if ( ! $flag ) {
    echo "  NOT FOUND or blacklisted\n";
  }
  echo "\n";
}
?>