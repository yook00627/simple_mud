<?php

require __DIR__ . "/vendor/autoload.php";

use Amp\Loop;
use Amp\Socket\ServerSocket;
use function Amp\asyncCall;

Loop::run(function () {
	$server = new class
	{
		//testing by hosting on local server connect by '$ nc localhost 1337'
		private $uri = "tcp://127.0.0.1:1337";

		private $clients = []; //list of clients
		private $usernames = []; //List of usernames
		private $playerPos = []; //position of players
		private $monsterPos = []; //placeholder for monster location

		private $rooms = array(
			array(
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 'U', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'U', 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
			),

			array(
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 0, 'D', 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 'U', 0, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1),
				array(1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 0, 'U', 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0, 'D', 0, 0, 0, 0, 1, 1, 1, 1),
				array(1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1),
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
			),

			array(
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'D', 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 'D', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
				array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
			)
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
						count($this->rooms[0][0]) / 2, count($this->rooms[0]) / 2, 0
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
							"If you wnat to see all command available type 'help'" . PHP_EOL
					);

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
						$socket->write(strtoupper(implode(" ", $args)) . PHP_EOL);
						break;

					case "down":
						$socket->write(strtolower(implode(" ", $args)) . PHP_EOL);
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
						$holder = $this->rooms[$position[2]][$position[1]][$position[0]];
						$this->rooms[$position[2]][$position[1]][$position[0]] = '+';
						$socket->write(PHP_EOL . "====              MAP              ====" . PHP_EOL);
						$socket->write($this->subArraysToString($this->rooms[$position[2]]));
						$this->rooms[$position[2]][$position[1]][$position[0]] = $holder;
						$socket->write(
							"=======================================" . PHP_EOL .
								"====       Player Position: +      ====" . PHP_EOL .
								"====         free space: 0         ====" . PHP_EOL .
								"====            Walls: 1           ====" . PHP_EOL .
								"====           stair up: U         ====" . PHP_EOL .
								"====          stair down: D        ====" . PHP_EOL .
								"=======================================" . PHP_EOL
						);
						break;

					case "say":
						if (trim(implode(" ", $args)) === "say") {
							break;
						}
						$user = $this->usernames[$socket->getRemoteAddress()];
						$this->broadcast($user . " says: " . implode(" ", $args) . PHP_EOL);
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

		//sending message to other clients
		private function broadcast(string $message)
		{
			foreach ($this->clients as $client) {
				$client->write($message);
			}
		}

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
});
