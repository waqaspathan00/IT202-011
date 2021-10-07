<?php
    require(__DIR__ . "/../../partials/nav.php");
?>

<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input type="email" name="email" required />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <div>
        <label for="confirm">Confirm</label>
        <input type="password" name="confirm" required minlength="8" />
    </div>
    <input type="submit" value="Register" />
</form>
<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation
        //ensure it returns false for an error and true for success

        return true;
    }
</script>
<?php
    if (isset($_POST["email"]) && isset($_POST["password"])  && isset($_POST["confirm"])){
        $email = $_POST["email"];
        $password = $_POST["password"];
        $confirm = $_POST["confirm"];
    }

    $errors = [];
    if (empty($email)){
        array_push($errors, "Email must be set");
    }

    // sanitize
    //$email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $email = sanitize_email($email);
     
    // validate
    //if(!filter_var($email, FILTER_SANITIZE_EMAIL)){
    if (!is_valid_email($email)){
        array_push($errors, "Invalid email address");
    }

    if (empty($password)){
        array_push($errors, "Password must be set");
    }
    if (empty($confirm)){
        array_push($errors, "Confirm password must be set");
    }
    if (strlen($password) < 8){
        array_push($errors, "Password must be 8 or more characters");
    }
    if (strlen($password) > 0 && $password !== $confirm){
        array_push($errors, "Passwords don't match");
    }
    if (count($errors) > 0){
        echo "<pre>" . var_export($errors, true) . "</pre>";
    }
    else{
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password) VALUES (:email, :password)");
        try{
            $stmt->execute([":email" => $email, ":password" => $hash]);
            echo "You've been registered!";
        } catch (Exception $e){
            echo "There was a problem registering";
            echo "<pre>" . var_export($e, true) . "</pre>";
        }
    }


?>