<?php
// Standalone logic test (no database needed).
require __DIR__ . '/src/Crypto.php';
require __DIR__ . '/src/PasswordGenerator.php';

use App\Crypto;
use App\PasswordGenerator;

$pass = 0; $fail = 0;
function check(string $name, bool $cond) {
    global $pass, $fail;
    echo ($cond ? "  PASS  " : "  FAIL  ") . $name . PHP_EOL;
    $cond ? $pass++ : $fail++;
}

echo "--- PasswordGenerator ---\n";
$gen = new PasswordGenerator(2, 3, 2, 2);        // the task's example: 9 chars
$pw  = $gen->generate();
check("length is 9", strlen($pw) === 9);
check("has 2 lowercase", preg_match_all('/[a-z]/', $pw) === 2);
check("has 3 uppercase", preg_match_all('/[A-Z]/', $pw) === 3);
check("has 2 digits",    preg_match_all('/[0-9]/', $pw) === 2);
check("has 2 specials",  preg_match_all('/[^a-zA-Z0-9]/', $pw) === 2);
echo "  sample: $pw\n";

$padded = new PasswordGenerator(1, 1, 1, 1, 16); // length > sum -> padding
check("padding -> length 16", strlen($padded->generate()) === 16);

$pct = PasswordGenerator::fromPercentages(20, 50, 30, 10, 10);
check("percentages -> length 20", strlen($pct->generate()) === 20);

$threw = false;
try { new PasswordGenerator(5, 5, 5, 5, 4); } catch (Throwable $e) { $threw = true; }
check("rejects length < sum", $threw);

echo "\n--- Crypto round-trip ---\n";
$key = Crypto::randomKey(32);
$ct  = Crypto::encrypt('secret value', $key);
check("decrypt returns original", Crypto::decrypt($ct, $key) === 'secret value');

$threw = false;
try { Crypto::decrypt($ct, Crypto::randomKey(32)); } catch (Throwable $e) { $threw = true; }
check("wrong key fails (GCM tag)", $threw);

echo "\n--- Envelope encryption flow (the core requirement) ---\n";
$masterOld = 'CorrectHorse9!';
$masterNew = 'NewMaster42#';

// Register: generate data KEY, wrap with old master password.
$dataKey = Crypto::randomKey(32);
$salt    = Crypto::randomKey(16);
$wrapped = Crypto::encrypt($dataKey, Crypto::deriveKey($masterOld, $salt));

// User saves a password, encrypted with the data KEY.
$vault = Crypto::encrypt('my-facebook-pw', $dataKey);

// Login: unwrap data KEY with master password.
$unwrapped = Crypto::decrypt($wrapped, Crypto::deriveKey($masterOld, $salt));
check("login unwraps same data KEY", $unwrapped === $dataKey);

// Change master password: re-wrap SAME data KEY with new password + new salt.
$newSalt    = Crypto::randomKey(16);
$rewrapped  = Crypto::encrypt($dataKey, Crypto::deriveKey($masterNew, $newSalt));

// Old password no longer unwraps it.
$threw = false;
try { Crypto::decrypt($rewrapped, Crypto::deriveKey($masterOld, $newSalt)); }
catch (Throwable $e) { $threw = true; }
check("old password can no longer unwrap", $threw);

// New password unwraps the unchanged KEY...
$afterChange = Crypto::decrypt($rewrapped, Crypto::deriveKey($masterNew, $newSalt));
check("new password unwraps same KEY", $afterChange === $dataKey);

// ...so the previously saved vault entry STILL decrypts.
check("saved password survives master change",
      Crypto::decrypt($vault, $afterChange) === 'my-facebook-pw');

echo "\n=== $pass passed, $fail failed ===\n";
exit($fail === 0 ? 0 : 1);
