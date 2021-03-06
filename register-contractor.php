<?php

	ini_set('max_input_time', 300);
	ini_set('max_execution_time', 300);

	/**
	 *	Create a function to randomly generate a string to be used as a salt to securely store the password.
	 */
	function generate_salt()
	{
		$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString     = '';

		for($i = 0; $i < 10; $i++)
		{
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	// Connects to database
	include_once('includes/functions.php');

	// Default user message empty
	$message = '';

	// Check if the form has been submitted
	if(isset($_POST['form_submission']) && ($_POST['form_submission'] == true))
	{
		// Store all posts as variables. The sanatize function prevents SQL injection and XSS attacks
		$email      			= sanatize($_POST['email']);
		$username   			= sanatize($_POST['username']);
		$password   			= sanatize($_POST['password']);
		$confirm_password = sanatize($_POST['confirm-password']);
		$skills						= json_encode(sanatize($_POST['skills']));
		$required 				= false;

		if(isset($_POST['first-name']) && isset($_POST['last-name']) && isset($_POST['date-of-birth']) && isset($_POST['personal-statement']) && isset($_POST['gender']))
		{
			$first_name	 	 			= sanatize($_POST['first-name']);
			$last_name  	 			= sanatize($_POST['last-name']);
			$date_of_birth 			= sanatize($_POST['date-of-birth']);
			$personal_statement = sanatize($_POST['personal-statement']);
			$gender  			 			= sanatize($_POST['gender']);

			// Confirm that all required fields have been filled in
			$required = true;
		}

		// Check all required details are complete
		if($email && $password && $confirm_password && $username && $required)
		{
			// Make sure the password and confirm password match (=== to make sure that capitals match too)
			if($password === $confirm_password)
			{
				// Query to find users with the same username
				$username_check_sql = "SELECT id
															 FROM users
															 WHERE username='$username';";

				$mysql_username_check_data = mysqli_query($login_connect, $username_check_sql);

				// Get the number of rows returned. 0 will mean the username does not exist in the database
				$username_count = mysqli_num_rows($mysql_username_check_data);

				// Make sure username does not exist in the database
				if($username_count == 0)
				{
					// Query to find users with the same username
					$email_check_sql = "SELECT id
															FROM users
															WHERE email='$email';";

					$mysql_email_check_data = mysqli_query($login_connect, $email_check_sql);

					// Get the number of rows returned. 0 will mean the username does not exist in the database
					$email_count = mysqli_num_rows($mysql_email_check_data);

					if($email_count == 0)
					{
						// Create random number to be used for the password salt
						$salt = generate_salt();

						// Hash the password with the random salt generated
						$password = hash_salt_password($password, $salt);

						// Query to insert user into database
						$insert_user_query = "INSERT INTO users (email, username, salt, password)
																	VALUES ('$email',
																					'$username',
																					'$salt',
																					'$password');";

						// Run query to insert the new contractor into the database
						mysqli_query($login_connect, $insert_user_query);

						$insert_id = mysqli_insert_id($login_connect);

						// Get information about the CV upload
						$cv_name  = $_FILES['cv']['name'];
						$cv_type  = $_FILES['cv']['type'];
						$cv_temp  = $_FILES['cv']['tmp_name'];
						$cv_path 	= "profiles/cv/";
						$cv 			= null;

						// Check that the user has uploaded a PDF. PDF's are the only file type which can be uploaded to the website
						if($cv_type == 'application/pdf')
						{
							if(is_uploaded_file($cv_temp))
							{
								// Move the PDF to the profiles/cv directory
								if(move_uploaded_file($cv_temp, $cv_path . $cv_name))
								{
									$cv = $cv_name;
								}
							}
						}
						else
						{
							$message = 'The CV Must be a PDF.';
						}

						$insert_contractor_query = "INSERT INTO `contractors` (`id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `bio`, `cv`, `gender`, `skills`)
																				VALUES (NULL,
																								'$insert_id',
																								'$first_name',
																								'$last_name',
																								'$date_of_birth',
																								'$personal_statement',
																								'$cv',
																								'$gender',
																								'$skills');";

						// Run query to insert the new contractor into the database
						mysqli_query($login_connect, $insert_contractor_query);

						$message = '<span id="success">Contractor created!</span>';
					}
					else
					{
						$message = '<span id="error">This email has already been used! Please login instead</span>';
					}
				}
				else
				{
					$message = '<span id="error">Username taken! please choose another</span>';
				}
			}
			else
			{
				$message = '<span id="error">Passwords do not match!</span>';
			}
		}
		else
		{
			$message = '<span id="error">Please fill in all required fields</span>';
		}
	}

?>

<!DOCTYPE html>
<html>

	<head>
		<!-- Link style sheets for this page -->
		<link rel="stylesheet" type="text/css" href="assets/css/master.css" />
		<link rel="stylesheet" type="text/css" href="assets/css/register.css" />
	</head>

	<body>
		<div id="form-container">
			<?php echo $message; ?>
			<h2>Register Contractor</h2>
			<form action="#" method="post" enctype="multipart/form-data">
				<p>
					<label>Email: <span class="required">*</span></label>
					<input type="email" name="email" required />
				</p>
				<p>
					<label>Username: <span class="required">*</span></label>
					<input type="text" name="username" required />
				</p>
				<p>
					<label>Password: <span class="required">*</span></label>
					<input type="password" name="password" required />
				</p>
				<p>
					<label>Confirm password: <span class="required">*</span></label>
					<input type="password" name="confirm-password" required />
				</p>
				<p class="contractor required">
					<label>First Name: <span class="required">*</span></label>
					<input type="text" name="first-name" required />
				</p>
				<p class="contractor required">
					<label>Last Name: <span class="required">*</span></label>
					<input type="text" name="last-name" required />
				</p>
				<p class="contractor required">
					<label>Date of birth: <span class="required">*</span></label>
					<input type="date" name="date-of-birth" required />
				</p>
				<p class="contractor required">
					<label>Gender: <span class="required">*</span></label>
					<select name="gender" required>
						<option value="male">Male</option>
						<option value="female">Female</option>
					</select>
				</p>
				<p class="contractor">
					<label>Personal Statement: </label>
					<textarea name="personal-statement"></textarea>
				</p>
				<p class="contractor">
					<label>Skills: <small>Seperate skills using a comma</small></label>
					<input type="text" name="skills" />
				</p>
				<p class="contractor">
					<label>CV: </label>
					<input type="file" name="cv" />
				</p>
				<input type="hidden" name="form_submission" value="true" /> <!-- Used in PHP to check form submission -->
				<input type="submit" class="button" value="Submit" />
				<a href="index.php" class="login-button">Login</a>
			</form>
		</div>
	</body>

</html>
