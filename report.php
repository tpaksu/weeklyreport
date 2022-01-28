<?php 
// phpcs:ignoreFile

$username = $_GET['user'] ?? false;
$ghtoken = $_GET['token'] ?? false;

if(!$username || !$ghtoken) die("We need your GitHub user and the token to enter, in the querystring.");

function get_json($url, $token)
{
    $base = "https://api.github.com";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $base . $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'PR and issues per week');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Token '.$token,
    ));
      //curl_setopt($curl, CONNECTTIMEOUT, 1);
    $content = curl_exec($curl);
    curl_close($curl);
  
    return json_decode($content, true);
}

function get_labels($result){
    $output = "";
    foreach($result["labels"] as $label){
        $output.="<span style='display: inline-block; border-radius: 5px; padding: 3px 4px; margin: 0 3px 3px 0; background: #".$label['color']."'><span style='color:white; mix-blend-mode: exclusion;'>" . $label["name"] . "</span></span>";
    }
    return $output;
}

function get_repository($result){
    $repo = $result['repository_url'];
    $repo = array_slice(explode('/',$repo), -2, 2);
    return implode('/',$repo);
}

echo "<pre>";
$pastweekstart = date('Y-m-d', strtotime('Monday -2 week'));
$pastweekend = date('Y-m-d', strtotime('Monday -1 week'));

echo "<h1>Weekly report for " . $pastweekstart . " - " . $pastweekend . "</h2>";

echo "<h2>Open issues</h2>";
$results = get_json("/search/issues?q=is:pr+is:open+sort:updated-desc+assignee:$username+updated:>={$pastweekstart}", $ghtoken);
echo "<ul>";
foreach($results["items"] as $result) {
    echo "<li>" . get_repository($result) . " - " . get_labels($result) . $result["state"] . "(".$result["created_at"].") " . " <a href='".str_replace(['api.','/repos'],'',$result['url'])."' target='_blank'>" . $result["title"] . "</a></li>";
}
echo "</ul>";

echo "<h2>Merged issues</h2>";
$results = get_json("/search/issues?q=is:pr+is:merged+sort:merged-desc+assignee:$username+merged:>={$pastweekstart}", $ghtoken);
echo "<ul>";
foreach($results["items"] as $result) {
    echo "<li>" . get_repository($result) . " - " . get_labels($result) . $result["state"] . "(".$result["closed_at"].") " . " <a href='".str_replace(['api.','/repos'],'',$result['url'])."' target='_blank'>" . $result["title"] . "</a></li>";
}
echo "</ul>";

echo "<h2>Reviewed issues</h2>";
$results = get_json("/search/issues?q=is:pr+reviewed-by:$username+-assignee:$username+sort:updated-desc+updated:>={$pastweekstart}", $ghtoken);
echo "<ul>";
foreach($results["items"] as $result) {
    echo "<li>" . get_repository($result) . " - " . get_labels($result) . $result["state"] . "(".$result["closed_at"].") " . " <a href='".str_replace(['api.','/repos'],'',$result['url'])."' target='_blank'>" . $result["title"] . "</a></li>";
}
echo "</ul>";
