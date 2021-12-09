<?php
require_once(__DIR__ . "/db.php");
$BASE_PATH = '/Project/'; //This is going to be a helper for redirecting to our base project path since it's nested in another folder

function debug($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

function se($v, $k = null, $default = "", $isEcho = true)
{
    if (is_array($v) && isset($k) && isset($v[$k])) {
        $returnValue = $v[$k];
    } else if (is_object($v) && isset($k) && isset($v->$k)) {
        $returnValue = $v->$k;
    } else {
        $returnValue = $v;
        //added 07-05-2021 to fix case where $k of $v isn't set
        //this is to kep htmlspecialchars happy
        if (is_array($returnValue) || is_object($returnValue)) {
            $returnValue = $default;
        }
    }
    if (!isset($returnValue)) {
        $returnValue = $default;
    }
    if ($isEcho) {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        echo htmlspecialchars($returnValue, ENT_QUOTES);
    } else {
        //https://www.php.net/manual/en/function.htmlspecialchars.php
        return htmlspecialchars($returnValue, ENT_QUOTES);
    }
}
//TODO 2: filter helpers
function sanitize_email($email = "")
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}
function is_valid_email($email = "")
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
//TODO 3: User Helpers
function is_logged_in($redirect = false, $destination = "login.php")
{
    $isLoggedIn = isset($_SESSION["user"]);
    if ($redirect && !$isLoggedIn) {
        flash("You must be logged in to view this page", "warning");
        die(header("Location: $destination"));
    }
    return $isLoggedIn; //se($_SESSION, "user", false, false);
}
function has_role($role)
{
    if (is_logged_in() && isset($_SESSION["user"]["roles"])) {
        foreach ($_SESSION["user"]["roles"] as $r) {
            if ($r["name"] === $role) {
                return true;
            }
        }
    }
    return false;
}
function get_username()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "username", "", false);
    }
    return "";
}
function get_user_email()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "email", "", false);
    }
    return "";
}
function get_user_id()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "id", false, false);
    }
    return false;
}
//TODO 4: Flash Message Helpers
function flash($msg = "", $color = "info")
{
    $message = ["text" => $msg, "color" => $color];
    if (isset($_SESSION['flash'])) {
        array_push($_SESSION['flash'], $message);
    } else {
        $_SESSION['flash'] = array();
        array_push($_SESSION['flash'], $message);
    }
}

function getMessages()
{
    if (isset($_SESSION['flash'])) {
        $flashes = $_SESSION['flash'];
        $_SESSION['flash'] = array();
        return $flashes;
    }
    return array();
}
//TODO generic helpers
function reset_session()
{
    session_unset();
    session_destroy();
}
function users_check_duplicate($errorInfo)
{
    if ($errorInfo[1] === 1062) {
        //https://www.php.net/manual/en/function.preg-match.php
        preg_match("/Users.(\w+)/", $errorInfo[2], $matches);
        if (isset($matches[1])) {
            flash("The chosen " . $matches[1] . " is not available.", "warning");
        } else {
            //TODO come up with a nice error message
            flash("<pre>" . var_export($errorInfo, true) . "</pre>");
        }
    } else {
        //TODO come up with a nice error message
        flash("<pre>" . var_export($errorInfo, true) . "</pre>");
    }
}
function get_url($dest)
{
    global $BASE_PATH;
    if (str_starts_with($dest, "/")) {
        //handle absolute path
        return $dest;
    }
    //handle relative path
    return $BASE_PATH . $dest;
}

function save_score( $user_id, $score, $showFlash = false)
{
    if ($user_id < 1) {
        flash("Error saving score, you may not be logged in", "warning");
        return;
    }
    if ($score <= 0) {
        flash("Scores of zero are not recorded", "warning");
        return;
    }
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Scores (user_id, score) VALUES (:uid, :score)");
    try {
        $stmt->execute([":uid" => $user_id, ":score" => $score]);
        if ($showFlash) {
            flash("Saved score of $score", "success");
        }
    } catch (PDOException $e) {
        flash("Error saving score: " . var_export($e->errorInfo, true), "danger");
    }
}

function get_top_10($duration){
    $db = getDB();
    $query = "SELECT user_id, username, score, Scores.modified FROM Scores JOIN Users ON Scores.user_id = Users.id";

    // the next few lines of code append functionality to the query string
    if ($duration !== "lifetime"){
        // if interval is not lifetime (IE week or month) then we need to filter the results 
        $query .= " WHERE Scores.modified >= DATE_SUB(NOW(), INTERVAL 1 $duration)";
    }
    // regardless of interval, we only want the best 10 results
    $query .= " ORDER BY score DESC, Scores.modified DESC LIMIT 10";

    error_log($query);
    $stmt = $db->prepare($query);
    $results = [];
    try {
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r){
            $results = $r;
        }
    } catch (PDOException $err){
        error_log("Error fetching scores for $duration: " . var_export($err->errorInfo, true));
    }

    // var_export($results);
    return $results;
}

function get_points(){
    if (is_logged_in() && isset($_SESSION["user"]["points"])){
        return (int)se($_SESSION["user"]["points"], "points", 0, false);
    }
    return 0;
}

function points_update()
{
    if (is_logged_in()) {
        $query = "UPDATE Users SET points = (SELECT IFNULL(SUM(point_change), 0) FROM PointsHistory WHERE user_id = :uid) WHERE id = :uid";
        $db = getDB();
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([":uid" => get_user_id()]);
            get_or_create_user(); //refresh session data
        } catch (PDOException $e) {
            flash("Error refreshing account: " . var_export($e->errorInfo, true), "danger");
        }
    }
}

/**
 * Will fetch the account of the logged in user, or create a new one if it doesn't exist yet.
 * Exists here so it may be called on any desired page and not just login
 * Will populate/refresh $_SESSION["user"]["account"] regardless.
 * Make sure this is called after the session has been set
 */
function get_or_create_user()
{
    if (is_logged_in()) {
        //let's define our data structure first
        //id is for internal references, account_number is user facing info, and balance will be a cached value of activity
        $user = ["id" => -1, "points" => 0];
        //this should always be 0 or 1, but being safe
        $query = "SELECT id, points from Users where id = :uid LIMIT 1";
        $db = getDB();
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([":uid" => get_user_id()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $result;
            $user["id"] = $result["id"];
            $user["points"] = $result["points"];
            $created = true;
        } catch (PDOException $e) {
            flash("Technical error: " . var_export($e->errorInfo, true), "danger");
        }
        $_SESSION["user"]["points"] = $user; //storing the account info as a key under the user session
        
    } else {
        flash("You're not logged in", "danger");
    }
}

function change_points($points, $reason) {
    $query = "INSERT INTO PointsHistory (user_id, point_change, reason) 
        VALUES (:uid, :pc, :r)";
    //I'll insert both records at once, note the placeholders kept the same and the ones changed.
    $params[":uid"] = get_user_id();
    $params[":pc"] = $points;
    $params[":r"] = $reason;
    $db = getDB();
    $stmt = $db->prepare($query);
    try {
        $stmt->execute($params);
        //added for module 10 to only refresh the logged in user's account
        //if it's part of src or dest since this is called during competition winner payout
        //which may not be the logged in user
        points_update();
        /*
        if ($src === get_user_account_id() || $dest === get_user_account_id()) {
            refresh_account_balance();
        }
        */
    } catch (PDOException $e) {
        flash("Transfer error occurred: " . var_export($e->errorInfo, true), "danger");
    }
}

function join_competition($competition_id, $isCreator = false) {
    if ($competition_id <= 0) {
        flash("Invalid Competition");
    }
    $db = getDB();
    $query = "SELECT name, current_reward, join_fee, paid_out FROM Competitions where id = :id";
    $stmt = $db->prepare($query);
    $comp = [];
    try {
        $stmt->execute([":id" => $competition_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $comp = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching competition to join $competition_id: " . var_export($e->errorInfo, true));
        flash("Error looking up competition");
    }
    if ($comp && count($comp) > 0) {
        $paid_out = (int)se($comp, "paid_out", 0, false) > 0;
        if ($paid_out) {
            flash("You can't join a completed competition");
        }
        $points = (int)se(get_points(), null, 0, false);
        $join_fee = (int)se($comp, "join_fee", 0, false);
        $name = se($comp, "name", 0, false);
        if ($join_fee > $points) {
            flash("You can't afford to join this competition");
        }
        $query = "INSERT INTO CompetitionParticipants (competition_id, user_id) VALUES (:cid, :uid)";
        $stmt = $db->prepare($query);
        $joined = false;
        try {
            $stmt->execute([":cid" => $competition_id, ":uid" => get_user_id()]);
            $joined = true;
        } catch (PDOException $e) {
            $err = $e->errorInfo;
            if ($err[1] === 1062) {
                return "You already joined this competition";
            }
            error_log("Error joining competition (UserCompetitions): " . var_export($err, true));
        }
        if ($joined) {
            if ($join_fee == 0){
                $reward_increase = 1;
            } else {
                $reward_increase = ceil(0.5 * $join_fee);
            }
            $query = "UPDATE Competitions set 
            current_participants = (SELECT count(1) from CompetitionParticipants WHERE competition_id = :cid),
            current_reward = current_reward + $reward_increase
            WHERE id = :cid";
            $stmt = $db->prepare($query);
            try {
                $stmt->execute([":cid" => $competition_id]);
            } catch (PDOException $e) {
                error_log("Error updating competition stats: " . var_export($e->errorInfo, true));
                //I'm choosing not to let failure here be a big deal, only 1 successful update periodically is required
            }
            if ($isCreator) {
                $join_fee = 0;
            }
            change_points(-$join_fee, "Joined Competition " . $competition_id, -1, true);
            flash("Successfully joined Competition \"$name\"");
        } else {
            flash("Unknown error joining competition, please try again");
        }
    } else {
        flash("Competition not found.");
    }
}

function calc_winners()
{
    $db = getDB();
    error_log("Starting winner calc");
    $calced_comps = [];
    $stmt = $db->prepare("select id, title, first_place, second_place, third_place, current_reward, current_participants, min_participants 
    from Competitions where expires <= CURRENT_TIMESTAMP() AND current_participants >= min_participants LIMIT 10");
    try {
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $rc = $stmt->rowCount();
            error_log("Validating $rc comps");
            foreach ($r as $row) {
                $fp = floatval(se($row, "first_place", 0, false) / 100);
                $sp = floatval(se($row, "second_place", 0, false) / 100);
                $tp = floatval(se($row, "third_place", 0, false) / 100);
                $reward = (int)se($row, "current_reward", 0, false);
                $title = se($row, "title", "-", false);
                $fpr = ceil($reward * $fp);
                $spr = ceil($reward * $sp);
                $tpr = ceil($reward * $tp);
                $comp_id = se($row, "id", -1, false);
                
                try {
                    $r = get_top_scores_for_comp($comp_id, 3);
                    if ($r) {
                        $atleastOne = false;
                        foreach ($r as $index => $row) {
                            $aid = se($row, "account_id", -1, false);
                            $score = se($row, "score", 0, false);
                            $user_id = se($row, "user_id", -1, false);
                            if ($index == 0) {
                                if (change_points($fpr, "won-comp", -1, $aid, "First place in $title with score of $score")) {
                                    $atleastOne = true;
                                }
                                error_log("User $user_id First place in $title with score of $score");
                            } else if ($index == 1) {
                                if (change_points($spr, "won-comp", -1, $aid, "Second place in $title with score of $score")) {
                                    $atleastOne = true;
                                }
                                error_log("User $user_id Second place in $title with score of $score");
                            } else if ($index == 2) {
                                if (change_points($tpr, "won-comp", -1, $aid, "Third place in $title with score of $score")) {
                                    $atleastOne = true;
                                }
                                error_log("User $user_id Third place in $title with score of $score");
                            }
                        }
                        if ($atleastOne) {
                            array_push($calced_comps, $comp_id);
                        }
                    } else {
                        error_log("No eligible scores");
                    }
                } catch (PDOException $e) {
                    error_log("Getting winners error: " . var_export($e, true));
                }
            }
        } else {
            error_log("No competitions ready");
        }
    } catch (PDOException $e) {
        error_log("Getting Expired Comps error: " . var_export($e, true));
    }
    //closing calced comps
    // I DONT HAVE DID CALC COLUMN
    if (count($calced_comps) > 0) {
        $query = "UPDATE Competitions set did_calc = 1 AND did_payout = 1 WHERE id in ";
        $query = "(" . str_repeat("?,", count($calced_comps) - 1) . "?)";
        error_log("Close query: $query");
        $stmt = $db->prepare($query);
        try {
            $stmt->execute($calced_comps);
            $updated = $stmt->rowCount();
            error_log("Marked $updated comps complete and calced");
        } catch (PDOException $e) {
            error_log("Closing valid comps error: " . var_export($e, true));
        }
    } else {
        error_log("No competitions to calc");
    }
    //close invalid comps
    // I DONT HAVE DID CALC COLUMN
    $stmt = $db->prepare("UPDATE Competitions set did_calc = 1 WHERE expires <= CURRENT_TIMESTAMP() AND current_participants < min_participants AND did_calc = 0");
    try {
        $stmt->execute();
        $rows = $stmt->rowCount();
        error_log("Closed $rows invalid competitions");
    } catch (PDOException $e) {
        error_log("Closing invalid comps error: " . var_export($e, true));
    }
    error_log("Done calc winners");
}

//snippet from functions.php
function get_top_scores_for_comp($comp_id, $limit = 10)
{
    // what is rank
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM (SELECT s.user_id, s.score,s.modified, a.id as account_id, DENSE_RANK() OVER (PARTITION BY s.user_id ORDER BY s.score desc) as `rank` FROM Scores s
    JOIN CompetitionParticipants cp on cp.user_id = s.user_id
    JOIN Competitions c on cp.competition_id = c.id
    JOIN Users u on u.id = s.user_id
    WHERE c.id = :cid AND s.modified BETWEEN cp.created AND c.expires
    )as t where `rank` = 1 ORDER BY score desc LIMIT :limit");

    $scores = [];
    try {
        $stmt->bindValue(":cid", $comp_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $scores = $r;
        }
    } catch (PDOException $e) {
        flash("There was a problem fetching scores, please try again later", "danger");
        error_log("List competition scores error: " . var_export($e, true));
    }
    return $scores;
}