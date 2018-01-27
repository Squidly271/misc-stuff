#!/usr/bin/php
<?PHP

$outputPath = "/tmp/GitHub";
$singleRepo = $argv[1];
echo $singleRepo."\n";

function fixPopUpDescription($PopUpDescription) {
  $PopUpDescription = str_replace("'","&#39;",$PopUpDescription);
  $PopUpDescription = str_replace('"','&quot;',$PopUpDescription);
  $PopUpDescription = str_replace("<br>","\n",$PopUpDescription);
  $PopUpDescription = str_replace("<b>","",$PopUpDescription);
  $PopUpDescription = str_replace("</b>","",$PopUpDescription);
  $PopUpDescription = str_replace("<h3>","",$PopUpDescription);
  $PopUpDescription = str_replace("</h3>","",$PopUpDescription);

  return ($PopUpDescription);
}

function mySort($a, $b) {
  $sortKey = "Name";
  $sortDir = "Down";

  if ( $sortKey != "Downloads" )
  {
    $c = strtolower($a[$sortKey]);
    $d = strtolower($b[$sortKey]);
  } else {
    $c = $a[$sortKey];
    $d = $b[$sortKey];
  }

  $return1 = ($sortDir == "Down") ? -1 : 1;
  $return2 = ($sortDir == "Down") ? 1 : -1;

  if ($c > $d) { return $return1; }
  else if ($c < $d) { return $return2; }
  else { return 0; }
}



$templates = json_decode(file_get_contents("/tmp/community.applications/tempFiles/templates.json"),true);
$moderation = json_decode(file_get_contents("/var/lib/docker/unraid/community.applications.datastore/moderation.json"),true);
usort($templates, "mySort");

$repos = json_decode(file_get_contents("/tmp/community.applications/tempFiles/Repositories.json"),true);

foreach ($repos as $repo)
{
  $repoURL[$repo['name']] = $repo['url'];
  $repoName[] = $repo['name'];
}


foreach ($templates as $template)
{
  $template['RepoURL'] = $repoURL[$template['RepoName']];

  $repository['name'][$template['RepoName']][] = $template;
}
#print_r($templates);
natcasesort($repoName);
#print_r($repoName);

foreach($repoName as $repo)
{
  foreach ($templates as $template)
  {
    if ($template['RepoName'] == $repo)
    {
      $sortedRepo[$repo][] = $template;
    }
  }
  $finalRepos[$repo]['name'] = $repo;
  if ( is_array($sortedRepo[$repo]) )
  {
    $finalRepos[$repo]['templates'] = array_reverse($sortedRepo[$repo]);
  }
}

#print_r($finalRepos);

$i = 0;
foreach($finalRepos as $repo)
{
  if  ($singleRepo) {
    if ($singleRepo != $repo['name']) {
      continue;
    }
  }
  if (is_array($repo) )
  {
    $r = "[size=18pt][b]".$repo['name']."[/b][/size]";
    if ( ! stripos($repo['name'],"Plugin") ) {
      $r .= "\n[size=8pt][url]".$repoURL[$repo['name']]."[/url][/size]";
    }
    $o = $r."[table]";
    $flag = false;
#    print_r($repo);
#echo $repo['name']."\n";
    foreach($repo['templates'] as $template)
    {
      if ( $flag ) {
        if ( strlen($o) + strlen($output) > 19000 ) {
          $o .= "[/table]";
          echo "Saving $outputPath/forum_post$i\n";
          file_put_contents("$outputPath/forum_post".$i,$output.$o);
          $i = $i + 1;
          $o = "$r\n[b]Continued[/b][table]";
          unset($output);
        }
      }
      $flag = true;
      $o .= "[tr][td][img width=30]".$template['Icon']."[/img][/td]";
      $o .= "[td][b]".$template['Name']."[/td]";
      if ( $template['Overview'] )
      {
        $template['Description'] = $template['Overview'];
      }
      $o .= "[td][i]".htmlspecialchars_decode(fixPopUpDescription($template['Description']),ENT_QUOTES)."[/i]";
      if ( $moderation[$template['Repository']] ) {
        if ( $moderation[$template['Repository']]['Blacklist']) {
          $o .= "\n\n[color=red]Blacklisted within CA: ";
        } else {
          $o .= "\n\n[color=red]Moderator Comment: ";
        }
      
        $o .= $moderation[$template['Repository']]['ModeratorComment'];
      }
      $o .= "[/td]";
      
      if ( $template['Support'] ) {
        $o .= "[td][color=red][url=".$template['Support']."]Support[/url][/color][/td][/tr]";
      } else {
        $o .= "[/tr]";
      }

    }
    $o .= "[/table]";
  }

  if ( strlen($output) + strlen($o) > 19000 )
  {
    echo "Saving $outputPath/forum_post$i\n";
    file_put_contents("$outputPath/forum_post".$i,$output);

    $output = $o;

    $i = $i + 1;
  } else {
    $output .= $o;
  }
}
echo "Saving $outputPath/forum_post$i\n";
file_put_contents("$outputPath/forum_post".$i,$output);
?>

