<?php
//remember, API endpoints should only echo/output precisely what you want returned
//any other unexpected characters can break the handling of the response
$response = ["message" => "There was a problem saving your score"];
http_response_code(400);
$contentType = $_SERVER["CONTENT_TYPE"];
// flash("made it");


// error_log("Content Type $contentType");
if ($contentType === "application/json") {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true)["data"];
} else if ($contentType === "application/x-www-form-urlencoded") {
    $data = $_POST;
}

error_log(var_export($data, true));
// if (isset($data["score"]) && isset($data["data"])) {
if (isset($data["score"])){
    require_once(__DIR__ . "/../../../lib/functions.php");
    $db = getDB();
    session_start();
    $user_id = get_user_id();
    $score = (int)se($data, "score", 0, false);
    save_score($user_id, $score, true);
    $response["message"] = "Score Saved!";
}
echo json_encode($response);
