<?php

require __DIR__ . "/vendor/autoload.php";

use Amp\Loop;
use Amp\Socket\ServerSocket;
use function Amp\asyncCall;

Loop::run(
	function () {
		$server = new class
		{
			//testing by hosting on local server connect by '$ nc localhost 1337'
			private $uri = "tcp://127.0.0.1:1337";

			private $clients = []; //list of clients
			private $usernames = []; //List of usernames
			private $playerPos = []; //position of players

			private $levels = array(
				array(
					array(1, 1, 1, 1, 1, 1, 1),
					array(1, 0, 0, 0, 0, 0, 1),
					array(1, 0, 'U', 0, 0, 0, 1),
					array(1, 0, 0, 0, 0, 0, 1),
					array(1, 0, 0, 0, 'U', 0, 1),
					array(1, 0, 0, 0, 0, 0, 1),
					array(1, 1, 1, 1, 1, 1, 1)
				),

				array(
					array(1, 1, 1, 1, 1, 1, 1),
					array(1, 1, 0, 1, 1, 1, 1),
					array(1, 1, 'D', 0, 0, 'U', 1),
					array(1, 1, 0, 1, 0, 1, 1),
					array(1, 1, 0, 1, 'D', 1, 1),
					array(1, 'U', 0, 0, 0, 1, 1),
					array(1, 1, 1, 1, 1, 1, 1)
				),

				array(
					array(1, 1, 1, 1, 1, 1, 1),
					array(1, 1, 1, 1, 1, 1, 1),
					array(1, 0, 0, 0, 0, 'D', 1),
					array(1, 0, 1, 1, 0, 1, 1),
					array(1, 0, 1, 1, 0, 1, 1),
					array(1, 'D', 0, 0, 0, 1, 1),
					array(1, 1, 1, 1, 1, 1, 1)
				)
			);

			//all events messages in x y z cordinates as the key in strings
			private $events = array(
				"3 3 0" => ("This is the beginning room" . PHP_EOL),
				"2 2 0" => ("There is a stairs going up" . PHP_EOL),
				"4 4 0" => ("There is a stairs going up" . PHP_EOL),
				"2 2 1" => ("There is a stairs going down" . PHP_EOL),
				"4 4 1" => ("There is a stairs going down" . PHP_EOL),
				"5 2 0" => ("There is a stairs going up" . PHP_EOL),
				"2 5 0" => ("There is a stairs going up" . PHP_EOL),
				"5 2 2" => ("There is a stairs going down" . PHP_EOL),
				"2 5 2" => ("There is a stairs going down" . PHP_EOL)
			);

			//starting the server and listening on clients
			public function listen()
			{
				asyncCall(function () {
					$server = Amp\Socket\listen($this->uri); //setting address

					echo "Starting Server at " . $server->getAddress() . " ..." . PHP_EOL;

					while ($socket = yield $server->accept()) {
						$this->handleClient($socket);
					}
				});
			}

			//handle the client side loop
			private function handleClient(ServerSocket $socket)
			{
				asyncCall(
					function () use ($socket) {
						$remoteAddr = $socket->getRemoteAddress();

						echo "Connecting client: {$remoteAddr}" . PHP_EOL;

						$this->broadcast($remoteAddr . " entered the dungeon" . PHP_EOL);

						$this->clients[$remoteAddr] = $socket;
						$this->usernames[$remoteAddr] = $remoteAddr;
						//default starting position
						$this->playerPos[$remoteAddr] = array(
							floor(count($this->levels[0][0]) / 2), floor(count($this->levels[0]) / 2), 0
						); //x, y, room number


						$socket->write(
							"WELCOME ADVENTURER TO THE DUNGION!!!" . PHP_EOL .
								PHP_EOL .
								PHP_EOL .
								"Here are some instructions:" . PHP_EOL .
								PHP_EOL .
								"You are able to move north, south, east, west, up and down" . PHP_EOL .
								"by typing each command words." . PHP_EOL .
								"You can only go up and down where there are stairs." . PHP_EOL .
								"Cuurently your name is set to your IP address change it" . PHP_EOL .
								"by typing 'name' and the name you wnat to change it to." . PHP_EOL .
								"You can even talk to other players by typing 'say' or 'yell'" . PHP_EOL .
								"and than write what ever you want to say afterwards." . PHP_EOL .
								"You can view the map by typing 'map'" . PHP_EOL . PHP_EOL .
								"If you wnat to see all command available type 'help'" . PHP_EOL . PHP_EOL
						);

						$this->handleEvents($socket);

						$buffer = "";
						while (null !== $chunk = yield $socket->read()) {
							$buffer .= $chunk;

							while (($pos = strpos($buffer, "\n")) !== false) {
								$this->handleMessage($socket, substr($buffer, 0, $pos));
								$buffer = substr($buffer, $pos + 1);
							}
						}

						unset($this->clients[$remoteAddr]);

						echo "Disconnecting client: {$remoteAddr}" . PHP_EOL;
						$this->broadcast($remoteAddr . " left the dungeon" . PHP_EOL);
					}
				);
			}

			//handling all messages
			function handleMessage(ServerSocket $socket, string $message)
			{
				if ($message === "") {
					return; // ignoring empty messages
				} else {
					$args = explode(" ", $message); // parse message into parts separated by space
					$name = (count($args) > 1 ? strtolower(array_shift($args)) : trim($message)); // the first arg is our command name

					switch ($name) {
						case "time":
							$socket->write(date("l jS \of F Y h:i:s A") . PHP_EOL);
							break;

						case "up":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							if ($this->levels[$position[2]][$position[1]][$position[0]] === "U") {
								$socket->write("Going up" . PHP_EOL);
								$this->playerPos[$socket->getRemoteAddress()][2] += 1;
							} else {
								$socket->write("There is no stairs going up" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "down":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							if ($this->levels[$position[2]][$position[1]][$position[0]] === "D") {
								$socket->write("Going down" . PHP_EOL);
								$this->playerPos[$socket->getRemoteAddress()][2] += 1;
							} else {
								$socket->write("There is no stairs going down" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "north":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							try {
								if ($this->levels[$position[2]][$position[1] - 1][$position[0]] !== 1) {
									$socket->write("Moving to the room in the north" . PHP_EOL);
									$this->playerPos[$socket->getRemoteAddress()][1] -= 1;
								} else {
									$socket->write("You can't move there" . PHP_EOL);
								}
							} catch (Exception $e) {
								$socket->write("You can't leave the map!" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "south":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							try {
								if ($this->levels[$position[2]][$position[1] + 1][$position[0]] !== 1) {
									$socket->write("Moving to the room in the south" . PHP_EOL);
									$this->playerPos[$socket->getRemoteAddress()][1] += 1;
								} else {
									$socket->write("You can't move there" . PHP_EOL);
								}
							} catch (Exception $e) {
								$socket->write("You can't leave the map!" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "east":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							try {
								if ($this->levels[$position[2]][$position[1]][$position[0] + 1] !== 1) {
									$socket->write("Moving to the room in the east" . PHP_EOL);
									$this->playerPos[$socket->getRemoteAddress()][0] += 1;
								} else {
									$socket->write("You can't move there" . PHP_EOL);
								}
							} catch (Exception $e) {
								$socket->write("You can't leave the map!" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "west":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							try {
								if ($this->levels[$position[2]][$position[1]][$position[0] - 1] !== 1) {
									$socket->write("Moving to the room in the east" . PHP_EOL);
									$this->playerPos[$socket->getRemoteAddress()][0] -= 1;
								} else {
									$socket->write("You can't move there" . PHP_EOL);
								}
							} catch (Exception $e) {
								$socket->write("You can't leave the map!" . PHP_EOL);
							}
							//trigger event
							$this->handleEvents($socket);
							break;

						case "exit":
							$socket->end("Bye." . PHP_EOL);
							break;

						case "help":
							$socket->write(
								"---------------------------------------" . PHP_EOL .
									"--             Go Up: up             --" . PHP_EOL .
									"--           Go Down: down           --" . PHP_EOL .
									"--          Go north: north          --" . PHP_EOL .
									"--           Go West: west           --" . PHP_EOL .
									"--          Go South: south          --" . PHP_EOL .
									"--           Go East: east           --" . PHP_EOL .
									"--         Say to Roomm: say         --" . PHP_EOL .
									"--       Yell to Evryone: yell       --" . PHP_EOL .
									"--           Show Map: map           --" . PHP_EOL .
									"--         change name: name         --" . PHP_EOL .
									"---------------------------------------" . PHP_EOL
							);
							break;

						case "map":
							$position = $this->playerPos[$socket->getRemoteAddress()];
							$holder = $this->levels[$position[2]][$position[1]][$position[0]];
							$this->levels[$position[2]][$position[1]][$position[0]] = '+';
							$socket->write(PHP_EOL . "====              MAP              ====" . PHP_EOL);
							$socket->write($this->subArraysToString($this->levels[$position[2]]));
							$this->levels[$position[2]][$position[1]][$position[0]] = $holder; //setting the map back to original
							$socket->write(
								"=======================================" . PHP_EOL .
									"====       Player Position: +      ====" . PHP_EOL .
									"====         free space: 0         ====" . PHP_EOL .
									"====            Walls: 1           ====" . PHP_EOL .
									"====      room with stair up: U    ====" . PHP_EOL .
									"====     room with stair down: D   ====" . PHP_EOL .
									"=======================================" . PHP_EOL
							);
							break;

						case "say":
							if (trim(implode(" ", $args)) === "say") {
								break;
							}
							$user = $this->usernames[$socket->getRemoteAddress()];
							$this->say($user . " says: " . implode(" ", $args) . PHP_EOL, $socket);
							break;

						case "yell":
							if (trim(implode(" ", $args)) === "yell") {
								break;
							}
							$user = $this->usernames[$socket->getRemoteAddress()];
							$this->yell($user . " yells: " . implode(" ", $args) . PHP_EOL, $socket);
							break;

						case "tell":
							if (trim(implode(" ", $args)) === "tell") {
								$socket->write("Tell who?" . PHP_EOL);
								break;
							}
							$username = trim($args[0]);
							if (trim(implode(" ", $args)) === $username) {
								break;
							}
							array_shift($args);
							$user = $this->usernames[$socket->getRemoteAddress()];
							$this->tell($user . " tells: " . implode(" ", $args) . PHP_EOL, $socket, $username);
							break;

						case "name":
							$name = trim(implode(" ", $args));

							if (!preg_match("(^[a-z0-9-.]{3,20}$)i", $name) || $name === "name") {
								$error = "Username must only contain letters, digits and " .
									"its length must be between 3 and 20 characters.";
								$socket->write($error . PHP_EOL);
								return;
							}

							$remoteAddr = $socket->getRemoteAddress();
							$oldName = $this->usernames[$remoteAddr];
							$this->usernames[$remoteAddr] = $name;

							$this->broadcast($oldName . " is now " . $name . PHP_EOL);
							break;

						default:
							$socket->write("Unknown command: {$name}" . PHP_EOL);
							break;
					}

					return;
				}
			}

			//handling events
			private function handleEvents($socket)
			{
				$position = $this->playerPos[$socket->getRemoteAddress()];
				if (array_key_exists(implode(" ", $position), $this->events)) {
					$socket->write($this->events[implode(" ", $position)]);
				} else {
					//default message when there is no event in the room
					$socket->write("There is nothing special in this room" . PHP_EOL);
				}
				//show all people in the room
				foreach ($this->clients as $client) {
					if (
						$this->playerPos[$socket->getRemoteAddress()] === $this->playerPos[$client->getRemoteAddress()] &&
						$socket !== $client
					) {
						$socket->write($this->usernames[$client->getRemoteAddress()] . "is in this room" . PHP_EOL);
					}
				}
			}

			//sending message to other clients
			private function broadcast(string $message)
			{
				foreach ($this->clients as $client) {
					$client->write($message);
				}
			}

			//sending message to other players in the room
			private function say(string $message, $socket)
			{
				foreach ($this->clients as $client) {
					if ($socket === $client) {
						$client->write("I said something" . PHP_EOL);
					} else if ($this->playerPos[$socket->getRemoteAddress()] === $this->playerPos[$client->getRemoteAddress()]) {
						$client->write($message);
					}
				}
			}

			//sending message to everyone
			private function yell(string $message, $socket)
			{
				foreach ($this->clients as $client) {
					if ($socket === $client) {
						$client->write("I yelled!" . PHP_EOL);
					} else {
						$client->write($message);
					}
				}
			}

			//sending messgae to specific user
			private function tell(string $message, $socket, $username)
			{
				foreach ($this->clients as $client) {
					if ($socket === $client) {
						$client->write("I told " . $username . " somthing" . PHP_EOL);
					} else if ($username === $this->usernames[$client->getRemoteAddress()]) {
						$client->write($message);
					}
				}
			}

			//converting 2d array to a string
			private function subArraysToString($ar, $sep = ' ')
			{
				$str = '';
				foreach ($ar as $val) {
					$str .= implode($sep, $val);
					$str .= PHP_EOL; // add separator between sub-arrays
				}
				$str = rtrim($str, $sep); // remove last separator
				return $str;
			}
		};
		$server->listen();
	}
);
