# Simple MUD

To be able to run the program you must have PHP and Composer installed.

You can install it by running the bash script included.

```
$ ./setup.bash
```


After installing you can simply run the PHP server by shown below

```
$ php server.php &
```

While running open up another tab on the terminal and run nc or telnet like below

```
$ nc localhost 1337
```

You can open multiple terminals to test for other users joining the channel

# API used


Amp: for making connection simple

https://amphp.org/