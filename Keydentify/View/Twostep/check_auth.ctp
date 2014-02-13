<?php 
/**
 * Keydentify CakePHP Plugin - SDK PHP Web
 *
 * Keydentify(tm) : Two Factor Authentication (http://www.keydentify.com)
 * Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 * @link          http://www.keydentify.com Keydentify(tm) Two Factor Authentication
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>

<!-- Website secure by Keydentify - Two-Factor Authentication http://www.keydentify.com -->
<div id="keydentify">
<?php 
if (isset($html)) {
	echo $this->Html->css('Keydentify.keydentify', null, array('inline' => false));
	echo $this->Html->script(array('Keydentify.sockjs-0.3.4.min', 'Keydentify.vertxbus', 'Keydentify.keydentify'), array('inline' => true));
	echo $this->Form->create('Keydentify');
	echo $html;
	echo $this->Form->end();
}
?>
</div>
<!-- /Keydentify - Two-Factor Authentication -->