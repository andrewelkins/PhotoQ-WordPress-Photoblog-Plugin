<?php
require_once('PHPUnit/Framework.php');
require_once('MockPress/mockpress.php');


class MyTableAccessorTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    global $wpdb;
    unset($wpdb); // unsetting $wpdb ensures that your tests always create new mock objects for their use
    _reset_wp();
  }

  function testReadRows() {
    global $wpdb;

    $wpdb = $this->getMock('wpdb', array('get_var'));
    $wpdb->expects($this->once())->method('get_var')->will($this->returnValue(1));
    
    $mta = new MyTableAccessor();
    $this->assertEquals(1, $mta->get_status());
  }
}

class MyTableAccessor {
  function get_status() {
    global $wpdb;
    return $wpdb->get_var('SELECT status from my_table');
  }
}

?>