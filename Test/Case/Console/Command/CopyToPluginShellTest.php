<?php

App::uses('Shell', 'Console');
App::uses('CopyToPluginShell', 'CopyToPluginShell.Console/Command');
App::uses('File', 'Utility');

class AppShell extends Shell
{
	public $inputs = array();

	public function in($prompt, $options = null, $default = null)
	{
		if ($this->inputs) {
			return array_shift($this->inputs);
		}
	}
}

class FileDemo extends File
{

	public function copy($dest, $overwrite = true)
	{
		// nothing to do
		return true;
	}

	public function delete()
	{
		// nothing to do
		return true;
	}

}

class CopyToPluginShellTest extends CakeTestCase
{

	public function setUp()
	{
		parent::setUp();
	}

	protected function getInstance()
	{
		$shell = new CopyToPluginShell();
		$shell->fileClass = 'FileDemo';
		return $shell;
	}


	public function testGetMatchedArg()
	{
		$shell = $this->getInstance();
		$shell->inputs = array(
			'a',
			'y',
			'N',
		);
		$res = $shell->_getMatchedArg('', '/^[YN]$/i');
		$this->assertEquals($res, 'y');
	}

	public function testMain_args()
	{
		$shell = $this->getInstance();
		$shell->inputs = array(
			'Post',			// Singular
			'MyPlugin',		// Plugin
			'Y',			// toCore
			'Y',			// override
			'Y',			// remove
			'Y',			// backup
		);

		$shell->main();
		$this->assertEquals($shell->singularName, 'Post');
		$this->assertEquals($shell->pluralName, 'Posts');
		$this->assertEquals($shell->pluginName, 'MyPlugin');
		$this->assertEquals($shell->toCore, true);
		$this->assertEquals($shell->remove, true);
		$this->assertEquals($shell->backup, true);

		$shell = $this->getInstance();
		$shell->inputs = array(
			'Post',			// Singular
			'MyPlugin',		// Plugin
			'N',			// toCore
			'N',			// override
			'N',			// remove
		);

		$shell->main();
		$this->assertEquals($shell->singularName, 'Post');
		$this->assertEquals($shell->pluralName, 'Posts');
		$this->assertEquals($shell->pluginName, 'MyPlugin');
		$this->assertEquals($shell->toCore, false);
		$this->assertEquals($shell->remove, false);
		$this->assertEquals($shell->backup, false);

	}

	public function testMain_path()
	{
		$shell = $this->getInstance();
		$shell->inputs = array(
			'Post',			// Singular
			'MyPlugin',		// Plugin
			'Y',			// toCore
			'Y',			// override
			'Y',			// remove
			'Y',			// backup
		);

		$shell->main();

		$this->assertRegExp('|' . preg_quote(DS . 'Model' . DS) . '$|', $shell->paths['Model']);
		$this->assertRegExp('|' . preg_quote(DS . 'Controller' . DS) . '$|', $shell->paths['Controller']);
		$this->assertRegExp('|' . preg_quote(DS . 'View' . DS) . '$|', $shell->paths['View']);
		$this->assertRegExp('|' . preg_quote(DS . 'Test' . DS . 'Case' . DS . 'Model' . DS) . '$|',
			$shell->paths['TestModel']);
		$this->assertRegExp('|' . preg_quote(DS . 'Test' . DS . 'Case' . DS . 'Controller' . DS) . '$|',
			$shell->paths['TestController']);
		$this->assertRegExp('|' . preg_quote(DS . 'Test' . DS . 'Fixture' . DS) . '$|',
			$shell->paths['TestFixture']);

	}

	public function testMain_setTarget()
	{
		$shell = $this->getInstance();
		$shell->inputs = array(
			'Post',			// Singular
			'MyPlugin',		// Plugin
			'Y',			// toCore
			'Y',			// override
			'Y',			// remove
			'Y',			// backup
		);

		$shell->main();

		$this->assertEquals(
			$shell->targetFiles['Model']['src'],
			$shell->paths['Model'] . 'Post.php');
		$this->assertEquals(
			$shell->targetFiles['Model']['create'],
			$shell->pluginDir . $shell->pluginName . DS . 'Model' . DS . 'Post.php');
		$this->assertEquals(
			$shell->targetFiles['Model']['override'],
			true);
		$this->assertEquals(
			$shell->targetFiles['Model']['backup'],
			true);
		$this->assertEquals(
			$shell->targetFiles['Model']['backupPath'],
			$shell->paths['Model'] . 'Post.php' . $shell->backupExt);

		$this->assertEquals(
			$shell->targetFiles['Controller']['src'],
			$shell->paths['Controller'] . 'PostsController.php');
		$this->assertEquals(
			$shell->targetFiles['Controller']['create'],
			$shell->pluginDir . $shell->pluginName . DS . 'Controller' . DS . 'PostsController.php');
		$this->assertEquals(
			$shell->targetFiles['Controller']['override'],
			true);
		$this->assertEquals(
			$shell->targetFiles['Controller']['backup'],
			true);
		$this->assertEquals(
			$shell->targetFiles['Controller']['backupPath'],
			$shell->paths['Controller'] . 'PostsController.php' . $shell->backupExt);
	}

}
