<?php
declare(strict_types=1);
require_once("classes/user.php"); ?>

<!DOCTYPE html>

<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Form Validation Sample</title>
		
		<link href="styles/style.css" rel="stylesheet">
		<script src="scripts/script.js"></script>
		
		<style>
			form label {
				width: 175px;
			}
		</style>
		
		<script>
			const nameRegexPattern = /^[A-Za-z' ]+$/;
			
			function isFormValid() {
				const elementAssertion = InputValidator.validateElements(
					new ElementInfo("user-username", "Username", 3, 20, /^[A-Za-z0-9]+$/, "Username must only consist of English letters and/or digits."),
					new ElementInfo("user-firstname", "First name", 2, 20, nameRegexPattern, "First name must only consist of English letters, optionally along with space(s)."),
					new ElementInfo("user-surname", "Surname", 2, 20, nameRegexPattern, "Surname must only consist of English letters, optionally along with space(s)."),
					new ElementInfo("user-age", "Age", 2, 2, /^[0-9]{2}$/, Constants.ageNotInRangeMessage),
					new ElementInfo("user-password", "Password", 6, 20),
					new ElementInfo("user-passwordconfirmation", "Password confirmation", 6, 20),
					new ElementInfo("user-email", "Email", 7, 254, /^[A-Za-z0-9.+-]*[A-Za-z0-9]@[A-Za-z-]+(?:\.[A-Za-z]+)+$/, "Please enter your email address properly.")
				);

				const age = ElementManager.getNumericValue("user-age");

				const infoAssertion = Assertion.assertAll(
					new Assertion(age >= Constants.minimumUserAge && age <= Constants.maximumUserAge, Constants.ageNotInRangeMessage),
					new Assertion(ElementManager.isRadioButtonChecked("user-gender-male") || ElementManager.isRadioButtonChecked("user-gender-female"), "Please specify your gender."),
					new Assertion(ElementManager.getValue("user-password") === ElementManager.getValue("user-passwordconfirmation"), "Your password must be the same as your confirmation password.")
				);
				
				if (!elementAssertion.isTrue) {
					alert(elementAssertion.failureMessage);
					return false;
				}

				if (!infoAssertion.isTrue) {
					alert(infoAssertion.failureMessage);
					return false;
				}
				
				return true;
			}
		</script>
	</head>
	
	<body id="centered-body" style="height: 97vh;">
		<div class="main-form">
			<span class="centered-container centered-container-header">Registration Form</span>
			<hr>
			
			<form action="<?php echo(htmlspecialchars($_SERVER["PHP_SELF"])); ?>" method="post" onsubmit="return isFormValid();">
				<label for="user-username">Username:</label>
				<input type="text" id="user-username" name="user-username" maxlength="20"><br>
	
				<label for="user-firstname">First name:</label>
				<input type="text" id="user-firstname" name="user-firstname" maxlength="20"><br>
	
				<label for="user-surname">Surname:</label>
				<input type="text" id="user-surname" name="user-surname" maxlength="20"><br>
	
				<label for="user-age">Age:</label>
				<input type="text" id="user-age" name="user-age" maxlength="2"><br>
	
				<label>Gender:</label>
				<input type="radio" id="user-gender-male" name="user-gender" value="<?php echo(Gender::Male->value); ?>" checked>
				<label for="user-gender-male" style="width: 50px;">Male</label>
	
				<input type="radio" id="user-gender-female" name="user-gender" value="<?php echo(Gender::Female->value); ?>">
				<label for="user-gender-female" style="width: 50px;">Female</label><br>
	
				<label for="user-password">Password:</label>
				<input type="password" id="user-password" name="user-password" maxlength="20"><br>
	
				<label for="user-passwordconfirmation">Password confirmation:</label>
				<input type="password" id="user-passwordconfirmation" name="user-passwordconfirmation" maxlength="20"><br>
	
				<label for="user-email">Email:</label>
				<input type="text" id="user-email" name="user-email" maxlength="254"><br><br>
	
				<input type="submit" class="form-button" value="Register">
			</form>
		</div>
	</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST")
	exit();

require_once("classes/input_manager.php");
require_once("classes/database.php");
require_once("classes/alert.php");
// require_once("classes/header.php");

const NAME_REGEX_PATTERN = "/^[A-Za-z' ]+$/";

const SAFE_VARIABLES = [
	"user-username", "user-firstname", "user-surname", "user-age", "user-gender", "user-email"
];

const VARIABLES = [...SAFE_VARIABLES, "user-password", "user-passwordconfirmation"];

validateForm();
sanitizeInput();
registerUser();

// Header::setLocation("index.php");

function validateForm(): void {
	if (!InputManager::areAllSet(FormMethod::Post, ...VARIABLES))
		Alert::displayAndExit("Certain required form variables are missing. Please re-submit the form and try again.");
	
	$result = getFormValidationResult();
	
	if (!$result->isTrue())
		Alert::displayAndExit($result->getFailureMessage());
}

function sanitizeInput(): void {
	// Sanitizing passwords can be potentially unsafe and problematic
	InputManager::sanitizeAll(FormMethod::Post, ...SAFE_VARIABLES);
}

function registerUser(): void {
	$user = new User(
		$_POST["user-username"],
		$_POST["user-firstname"],
		$_POST["user-surname"],
		(int)$_POST["user-age"],
		Gender::from((int)$_POST["user-gender"]),
		$_POST["user-password"],
		$_POST["user-email"]
	);
	
	Database::connect();
	
	try {
		if (Database::query("SELECT * FROM user WHERE Username = ?;", "s", $user->getUsername())->num_rows > 0)
			Alert::displayAndExit("A user with the specified username already exists.");
		
		Database::query("INSERT INTO user VALUES (?, ?, ?, ?, ?, ?, ?);", "sssisss",
			$user->getUsername(), $user->getFirstname(), $user->getSurname(), $user->getAge(),
			$user->getGender()->name, $user->getPassword(), $user->getEmail()
		);
	}
	catch (Exception $ex) {
		Alert::displayAndExit("An error occurred while attempting to communicate with the database:\\n{$ex->getMessage()}");
	}
	
	Database::disconnect();
	Alert::display("Your account has been registered successfully.");
}

function getFormValidationResult(): Assertion {
	$result = InputManager::validateInput(
		new InputInfo($_POST["user-username"], "Username", 3, 20, "/^[A-Za-z0-9]+$/", "Username must only consist of English letters and/or digits."),
		new InputInfo($_POST["user-firstname"], "First name", 2, 20, NAME_REGEX_PATTERN, "First name must only consist of English letters, optionally with space(s) and an apostrophe."),
		new InputInfo($_POST["user-surname"], "Surname", 2, 20, NAME_REGEX_PATTERN, "Surname must only consist of English letters, optionally with space(s) and an apostrophe."),
		new InputInfo($_POST["user-age"], "Age", 2, 2, "/^[0-9]{2}$/", "Please enter your age properly."),
		new InputInfo($_POST["user-password"], "Password", 6, 20),
		new InputInfo($_POST["user-passwordconfirmation"], "Password confirmation", 6, 20),
		new InputInfo($_POST["user-email"], "Email", 7, 254, "/^[A-Za-z0-9.+-]*[A-Za-z0-9]@[A-Za-z-]+(?:\.[A-Za-z]+)+$/", "Please enter your email address properly.")
	);
	
	if (!$result->isTrue())
		return $result;
	
	$age = (int)$_POST["user-age"];
	
	return Assertion::assertAll(
		new Assertion(
			$age >= User::MINIMUM_AGE && $age <= User::MAXIMUM_AGE,
			sprintf("You must be at least %d years old, and at most %d years old in order to register an account.", User::MINIMUM_AGE, User::MAXIMUM_AGE)
		),
		new Assertion($_POST["user-password"] === $_POST["user-passwordconfirmation"], "Your password must be the same as your confirmation password."),
		new Assertion(Gender::tryFrom((int)$_POST["user-gender"]) != null, "Please select your gender properly.")
	);
}
