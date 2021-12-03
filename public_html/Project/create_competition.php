<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    flash("You must be logged in to access this page", "danger");

    die(header("Location: " . $BASE_PATH));
}
?>
<?php if (isset($_SESSION["user"])) {
    flash("test", "warning");
    $id = se($_POST, "id", false, false);
    $name = se($_POST, "name", false, false);
    $duration = (int)se($_POST, "duration", 3, false);
    $expires = se($_POST, "expires", 1, false);
    $current_reward = (int)se($_POST, "curent_reward", 1, false);
    $starting_reward = (int)se($_POST, "starting_reward", 1, false);
    $join_fee = (int)se($_POST, "join_fee", 0, false);
    $current_participants = (int)se($_POST, "current_participants", 0, false);
    $min_participants = (int)se($_POST, "min_participants", 3, false);
    $paid_out = false;
    $min_score = (int)se($_POST, "min_score", 1, false);
    $first_place_per = "1";
    $second_place_per = "0";
    $third_place_per = "0";
    $payout_split = se($_POST, "payout", 1, false);
    $cost_to_create = $starting_reward + 1;
    $points = (int)se(get_points(), null, 0, false);
   
    switch ($payout_split) {
        case "2":
            $first_place_per = "0.8";
            $second_place_per = "0.2";
            $third_place_per = "0";
            //$payout = ".8,.2";
            break;
        case "3":
            //$payout = ".7,.2,.1";
            $first_place_per = "0.7";
            $second_place_per = "0.2";
            $third_place_per = "0.1";
            break;
        case "4":
            //$payout = ".6,.3,.1";
            $first_place_per = "0.6";
            $second_place_per = "0.3";
            $third_place_per = "0.1";
            break;
        case "5":
            //$payout = ".34,.33,.33";
            $first_place_per = "0.34";
            $second_place_per = "0.33";
            $third_place_per = "0.33";
            break;
        default:
            //$payout = "1";
            $first_place_per = "1";
            $second_place_per = "0";
            $third_place_per = "0";
            break;
    }
    $isValid = true;
    //validate
    if ($starting_reward < 0) {
        flash("Invalid Starting Reward", "warning");
        $isValid = false;
    }
    if ($cost_to_create < 1) {
        flash("Invalid Cost", "danger");
        $isValid = false;
    }
    if ($cost_to_create > $points) {
        flash("You can't afford this, it requires $cost_to_create points", "warning");
        $isValid = false;
    }
    if ($min_participants < 3) {
        flash("All competitions require at least 3 participants to payout", "warning");
    }
    if (!!$name === false) {
        flash("Name must be set", "warning");
        $isValid = false;
    }

    /*
    if ($join_fee < 0) {
        flash("Entry fee must be free (0) or greater", "warning");
        $isValid = false;
    }
    */
    /*
    if ($reward_increase < 0.0 || $reward_increase > 1.0) {
        flash("The reward increase can only be between 0% - 100% of the Entry Fee", "warning");
        $isValid = false;
    }
    */
    if ($duration < 3 || is_nan($duration)) {
        flash("Competitions must be 3 or greater days", "warning");
        $isValid = false;
    }
    if ($isValid) {
        //create competition and deduct cost
        $db = getDB();
        //setting 1 for participants since we'll be adding creator to the comp, this saves an update query
        //using sql to calculate the expires date by passing in a sanitized/validated $duration
        //setting starting_reward and current_reward to the same value
        //$query = "INSERT INTO Competitions (name, creator, starting_reward, current_reward, min_participants, current_participants, entry_fee, reward_increase, payouts, expires)
        $query = "INSERT INTO Competitions (name, expires, current_reward, starting_reward, join_fee, current_participants, min_participants, min_score, first_place_per, second_place_per, third_place_per)
        values (:n, DATE_ADD(NOW(), INTERVAL $duration day), :cr ,:sr, :jf, 1, :mp, :ms, :fpp, :spp :tpp)";
        $stmt = $db->prepare($query);
        try {
            $stmt->execute([
                ":n" => $name,
                ":cr" => $current_reward,
                ":sr" => $starting_reward,
                ":jf" => $join_fee,
                ":mp" => $min_participants,
                ":ms" => $min_score,
                ":fpp" => $first_place_per,
                ":spp" => $second_place_per,
                ":tpp" => $third_place_per
            ]);
            $id = (int)$db->lastInsertId();
            if ($comp_id > 0) {
                change_points($cost, "Created Competition #$id");
                //TODO creator joins competition for free
                //error_log("Attempt to join created competition: " . join_competition($comp_id, true));
                flash("Successfully created Competition $name", "success");
            }
        } catch (PDOException $e) {
            error_log("Error creating competition: " . var_export($e->errorInfo, true));
            flash("There was an error creating the competition: " . var_export($e->errorInfo[2]), "danger");
        }
    }
}
?>
<h1> Create Competition </h1>
<div class="container-fluid">
    <form method="POST" autocomplete="off">
        <div>
            <label class="form-label" for="name">Name/Title</label>
            <input class="form-control" type="text" name="name" id="name" required />
        </div>
        <div>
            <label class="form-label" for="sr">Starting Reward</label>
            <input class="form-control" type="number" name="starting_reward" id="sr" min="1" value="1" oninput="document.getElementById('cost').innerText = 1 + (value*1)" required />
        </div>
        <div>
            <label class="form-label" for="ef">Entry Fee</label>
            <input class="form-control" type="number" name="entry_fee" id="ef" min="0" value="0" required />
        </div>
        <div>
            <label class="form-label" for="rp">Min. Required Participants</label>
            <input class="form-control" type="number" name="min_participants" id="rp" min="3" value="3" required />
        </div>
        <div>
            <label class="form-label" for="d">Duration in Days</label>
            <input class="form-control" type="number" name="duration" id="d" min="3" value="3" required />
        </div>
        <div>
            <label class="form-label" for="payout">Payout Split</label>
            <select class="form-control" name="payout" required>
                <option value="1">100% to First</option>
                <option value="2">80% to First, 20% to Second</option>
                <option value="3">70% to First, 20% to Second, 10% to Third</option>
                <option value="4">60% to First, 30% to Second, 10% to Third</option>
                <option value="5">34% to First, 33% to Second, 33% to Third</option>
            </select>
        </div>
        <div>Cost: <span id="cost">2</span></div>
        <input class="btn btn-dark" type="submit" value="Create" />
    </form>
</div>
<script>
    function validate(form) {
        //TODO add all validations (basically match what you define at the html level for consistency)

        //client side balance validation (just used to reduce server load as we don't trust the client)
        let balance = <?php se(get_points(), null, 0); ?> * 1; //convert to int
        let cost = 1 + (form.starting_reward.value * 1);
        if (cost < 1) {
            cost = 1;
        }
        let isValid = true;
        if (cost > balance) {
            flash("You can't afford to create this competition, you need " + cost + " points");
            isValid = false;
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>