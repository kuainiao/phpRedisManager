<?php

require_once 'includes/common.inc.php';

$info = array();

foreach ($config['servers'] as $i => $server) {
  if (!isset($server['db'])) {
      $server['db'] = 0;
  }

  // Setup a connection to Redis.
  $redis = new Redis();
  try {
      $redis->pconnect($server['host'],$server['port']);
  } catch (Exception $exception) {
      $redis = false;
  }

  if(!$redis) {
      $info[$i] = false;
  } else {
      if (isset($server['auth'])) {
        if (!$redis->auth($server['auth'])) {
          die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
        }
      }
      if ($server['db'] != 0) {
        if (!$redis->select($server['db'])) {
          die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
        }
      }

      $info[$i]         = $redis->info();
      $info[$i]['size'] = $redis->dbSize();

      if (!isset($info[$i]['Server'])) {
        $info[$i]['Server'] = array(
          'redis_version'     => $info[$i]['redis_version'],
          'uptime_in_seconds' => $info[$i]['uptime_in_seconds']
        );
      }
      if (!isset($info[$i]['Memory'])) {
        $info[$i]['Memory'] = array(
          'used_memory' => $info[$i]['used_memory']
        );
      }
  }


}




$page['css'][] = 'frame';
$page['js'][]  = 'frame';

require 'includes/header.inc.php';

?>

<?php foreach ($config['servers'] as $i => $server) { ?>
  <div class="server">
  <h2><?php echo isset($server['name']) ? format_html($server['name']) : format_html($server['host'])?></h2>

  <?php if(!$info[$i]): ?>
  <div style="text-align:center;color:red">Server Down</div>
  <?php else: ?>

  <table>

  <tr><td><div>Redis version:</div></td><td><div><?php echo $info[$i]['Server']['redis_version']?></div></td></tr>

  <tr><td><div>Keys:</div></td><td><div><?php echo $info[$i]['size']?></div></td></tr>

  <tr><td><div>Memory used:</div></td><td><div><?php echo format_size($info[$i]['Memory']['used_memory'])?></div></td></tr>

  <tr><td><div>Uptime:</div></td><td><div><?php echo format_ago($info[$i]['Server']['uptime_in_seconds'])?></div></td></tr>

  <tr><td><div>Last save:</div></td><td><div><?php if (isset($info[$i]['Persistence']['rdb_last_save_time'])) { echo format_ago(time() - $info[$i]['Persistence']['rdb_last_save_time'], true); } else { echo 'never'; } ?> <a href="save.php?s=<?php echo $i?>"><img src="images/save.png" width="16" height="16" title="Save Now" alt="[S]" class="imgbut"></a></div></td></tr>

  </table>
  <?php endif; ?>
  </div>
<?php } ?>

<p class="clear">
<a href="https://github.com/awinlei/phpRedisManager" target="_blank">phpRedisManager on GitHub</a>
</p>

<p>
<a href="http://redis.io/documentation" target="_blank">Redis Documentation</a>
</p>
<?php

require 'includes/footer.inc.php';

?>
