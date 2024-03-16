<?php
enum Gender: int {
	case Male   = 0;
	case Female = 1;
}

readonly class User {
	public const MINIMUM_AGE = 18;
	public const MAXIMUM_AGE = 80;
	
	private const PASSWORD_ALGORITHM = PASSWORD_DEFAULT;
	
	private string $username;
	private string $firstname;
	private string $surname;
	
	private int $age;
	
	private Gender $gender;
	private string $password;
	private string $email;
	
	public function getUsername(): string {
		return $this->username;
	}
	
	public function getFirstname(): string {
		return $this->firstname;
	}
	
	public function getSurname(): string {
		return $this->surname;
	}
	
	public function getAge(): int {
		return $this->age;
	}
	
	public function getGender(): Gender {
		return $this->gender;
	}
	
	public function getPassword(): string {
		return $this->password;
	}
	
	public function getEmail(): string {
		return $this->email;
	}
	
	public function doesPasswordMatch(string $targetPassword): bool {
		return password_verify($targetPassword, $this->password);
	}
	
	public function __construct(string $username,
	                            string $firstname,
	                            string $surname,
	                            int $age,
	                            Gender $gender,
	                            string $password,
	                            string $email) {
		if ($age < self::MINIMUM_AGE || $age > self::MAXIMUM_AGE)
			throw new InvalidArgumentException(sprintf("Age must be between %d and %d.", self::MINIMUM_AGE, self::MAXIMUM_AGE));
		
		$this->username  = $username;
		$this->firstname = $firstname;
		$this->surname   = $surname;
		$this->age       = $age;
		$this->gender    = $gender;
		$this->password  = password_hash($password, self::PASSWORD_ALGORITHM);
		$this->email     = $email;
	}
}
