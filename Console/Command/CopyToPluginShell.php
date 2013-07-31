<?php
/**
 *
 * This is a Shell class to copy the files for plugin from the baked files.
 *
 * プラグインを作成するときに、アプリケーションでベイクしたファイルをごっそりコピーする、そんなシェルです。
 * 本当は、ベイクするときにプラグイン用のファイルをプラグインディレクトリに作成出来ればいいんですが、ベイク関連のカスタマイズが上手く行かなかったので苦肉の策です。
 * （後日、 plugin コマンドがあるのを見つけたのですが、上手く動いてくれない）
 * ファイルの内容は可能な限りプラグインの空間にリプレイスしています。
 * 従って、依存している他のモデルやフィクスチャで同じプラグインに含まれていないものは生成後に手動で書きなおす必要があります。
 *
 * ファイルI/O のみテスト済み
 *
 * 使い方
 * 		1 : Console/cake bake all などで、普通にファイルを bake する
 *		2 : Console/cake CopyToPluginShell.CopyToPlugin でこのシェルを呼び出す
 *		3 : パラメータを色々聞いてくるので適当に入力して下さい。パラメータは以下のようなものがあります
 *          Model Name (singular)
 *              モデルの名前を通常のやり方（単数形キャメルケース）で入力
 *          Plugin Name
 *              コピー先のプラグイン名を入力
 *          To Core ?
 *              コアのプラグインか（"N" の場合、アプリケーションのプラグインにコピーされる）
 *          Override If Exists ?
 *              コピー先に同名ファイルがある場合に上書きするか？
 *          Remove Original ?
 *              コピー元のデータを削除するか？
 *          Create Backup ?
 *              コピー元のデータのバックアップを作成するか？（Remove Original が有効の場合のみのオプション） *
 */


App::uses('AppShell', 'Console/Command');
App::uses('Inflector', 'Utility');
App::uses('File', 'Utility');

class CopyToPluginShell extends AppShell
{

	public $paths = array();

	public $singularName = null;

	public $pluralName = null;

	public $pluginName = null;

	public $pluginUnderscoredName = null;

	public $pluginDir = null;

	public $toCore = false;

	public $backup = false;

	public $remove = true;

	public $targetFiles = array();

	public $backupExt = '.BAK';

	public $override = false;

	public $fileClass = 'File'; // For creating test case, you may change file control class.

	public function main()
	{
		$paths = App::paths();
		$this->paths = array(
			'Model' => $paths['Model'][0],
			'Controller' => $paths['Controller'][0],
			'View' => $paths['View'][0],
			'TestModel' => APP . 'Test' . DS . 'Case' . DS . 'Model' . DS,
			'TestController' => APP . 'Test' . DS . 'Case' . DS . 'Controller' . DS,
			'TestFixture' => APP . 'Test' . DS . 'Fixture' . DS,
		);

		$this->singularName =
			Inflector::camelize($this->_getMatchedArg('Model Name (singular)', '/^[a-zA-Z][_ a-zA-Z0-9]*$/'));
		$this->pluralName =
			Inflector::pluralize($this->singularName);
		$this->pluginName =
			Inflector::camelize($this->_getMatchedArg('Plugin Name', '/^[a-zA-Z][_ a-zA-Z0-9]*$/'));
		$this->pluginUnderscoredName =
			Inflector::underscore($this->pluginName);
		$this->toCore =
			strtoupper($this->_getMatchedArg('To Core ? [Y]es or [N]o', '/^[NY]$/i', array('N', 'Y'), 'N')) === 'Y';
		$this->override =
			strtoupper($this->_getMatchedArg('Override If Exists ? [Y]es or [N]o', '/^[NY]$/i', array('N', 'Y'), 'N')) === 'Y';
		$this->remove =
			strtoupper($this->_getMatchedArg('Remove Original ? [Y]es or [N]o', '/^[NY]$/i', array('N', 'Y'), 'Y')) === 'Y';
		if ($this->remove) {
			$this->backup =
				strtoupper($this->_getMatchedArg('Create Backup ? [Y]es or [N]o', '/^[NY]$/i', array('N', 'Y'), 'Y')) === 'Y';
		}

		$this->out(sprintf("\n" . 'Check Your Configurations' . "\n" .
				'    Singular Name      : %s' . "\n" .
				'    Plural Name        : %s' . "\n" .
				'    plugin             : %s' . "\n" .
				'    override if exists : %s' . "\n" .
				'    to core            : %s' . "\n" .
				'    remove             : %s',
			$this->singularName,
			$this->pluralName,
			$this->pluginName,
			$this->override ? 'YES' : 'NO',
			$this->toCore ? 'YES' : 'NO',
			$this->remove ? 'YES' : 'NO'
		));

		if ($this->remove) {
			$this->out('    backup             : ' . ($this->backup ? 'YES' : 'NO'));
		}

		if ($this->toCore) {
			$this->pluginDir = dirname(CORE_PATH ) . DS . 'plugins' . DS;
		} else {
			$this->pluginDir = APP . 'Plugin' . DS;
		}

		if (strtoupper($this->in("\n" . 'They\'re OK?', array('Y', 'N'))) === 'N') {
			$this->out('Good bye!');
			return;
		}

		foreach ($this->paths as $type => $path) {
			$this->_setTarget($type, $path);
		}

		$this->_move();
	}

	public function _getMatchedArg($title, $pattern, $options = null, $defaults = null)
	{
		do {
			$arg = $this->in($title . ' :', $options, $defaults);
			if (!preg_match($pattern, $arg)) {
				$arg = null;
			}
		} while (empty($arg));

		return $arg;
	}

	public function _setTarget($type, $path)
	{
		$pluginDir = $this->pluginDir . $this->pluginName . DS;

		switch($type) {
			case 'Model':
				$this->targetFiles['Model'] = array(
					'src' => $path . $this->singularName . '.php',
					'create' => $pluginDir . 'Model' . DS . $this->singularName . '.php',
					'override' => $this->override,
					'remove' => $this->remove,
					'backup' => $this->backup,
					'backupPath' => $path . $this->singularName . '.php' . $this->backupExt,
				);
				break;
			case 'Controller':
				$this->targetFiles['Controller'] = array(
					'src' => $path . $this->pluralName . 'Controller.php',
					'create' => $pluginDir . 'Controller' . DS . $this->pluralName . 'Controller.php',
					'override' => $this->override,
					'remove' => $this->remove,
					'backup' => $this->backup,
					'backupPath' => $path . $this->pluralName . 'Controller.php' . $this->backupExt,
				);
				break;
			case 'View':
				if (!is_dir($path . DS . $this->pluralName)) {
					return false;
				}
				$dh = opendir($path . DS . $this->pluralName);
				while (($file = readdir($dh)) !== false) {
					if (strpos($file, '.ctp')) {
						$this->targetFiles['View.' . substr($file, 0, - 4)] = array(
							'src' => $path . $this->pluralName . DS . $file,
							'create' => $pluginDir . 'View' . DS . $this->pluralName . DS . $file,
							'override' => $this->override,
							'remove' => $this->remove,
							'backup' => $this->backup,
							'backupPath' => $path . $this->pluralName . DS . $file . $this->backupExt,
						);
					}
				}
				break;
			case 'TestModel':
				$this->targetFiles['TestModel'] = array(
					'src' => $path . $this->singularName . 'Test.php',
					'create' => $pluginDir . 'Test' . DS . 'Case' . DS . 'Model' . DS . $this->singularName . 'Test.php',
					'override' => $this->override,
					'remove' => $this->remove,
					'backup' => $this->backup,
					'backupPath' => $path . $this->singularName . 'Test.php' . $this->backupExt,
				);
				break;
			case 'TestController':
				$this->targetFiles['TestController'] = array(
					'src' => $path . $this->pluralName . 'ControllerTest.php',
					'create' => $pluginDir. 'Test' . DS . 'Case' . DS . 'Controller' . DS . $this->pluralName . 'ControllerTest.php',
					'override' => $this->override,
					'remove' => $this->remove,
					'backup' => $this->backup,
					'backupPath' => $path . $this->pluralName . 'ControllerTest.php' . $this->backupExt,
				);
				break;
			case 'TestFixture':
				$this->targetFiles['TestFixture'] = array(
					'src' => $path . $this->singularName . 'Fixture.php',
					'create' => $pluginDir. 'Test' . DS . 'Fixture' . DS . $this->singularName . 'Fixture.php',
					'override' => $this->override,
					'remove' => $this->remove,
					'backup' => $this->backup,
					'backupPath' => $path . $this->singularName . 'Fixture.php' . $this->backupExt,
				);
				break;
			default:
				// nothing to do
				break;
		}
	}

	public function _move()
	{
		$fileClass = $this->fileClass;

		foreach ($this->targetFiles as $k => $settings) {
			$this->out("\n[" . $k . ']');
			$file = new $fileClass($settings['src']);

			// check existence
			if (! $file->exists()) {
				$this->out('  Sorry, all are skipped because the source file is not exists.');
				continue;
			}

			// copy
			if (file_exists($settings['create'])) {
				if (! $settings['override']) {
					$this->out('    create : skip (because the file is exists.)');
					continue;
				}
			} else {
				new $fileClass($settings['create'], true);
			}

			if ( $file->copy($settings['create'])) {
				$this->out('    copy  : success');
			} else {
				$this->out('    copy  : FAULT !');
				continue;
			}

			if ($refactored = $this->_refactor(file_get_contents($settings['create']), $k)) {
				$fp = fopen($settings['create'], 'w');
				if (fwrite($fp, $refactored)) {
					$this->out('    refactor : success');
				} else {
					$this->out('    refactor : FAULT !');
				}
			} else {
				$this->out('    refactor : skipped...');
			}

			// backup
			if ($settings['backup']) {
				if (!file_exists($settings['backupPath'])) {
					new $fileClass($settings['backupPath'], true);
				}

				if ($file->copy($settings['backupPath'])) {
					$this->out('    backup: success');
				} else {
					$this->out('    backup: FAULT !');
					continue;
				}
			}

			// remove
			if ($settings['remove']) {
				if($file->delete()) {
					$this->out('    remove: success');
				} else {
					$this->out('    remove: FAULT !');
					continue;
				}
			}

			$this->out('  Whoooo! All are succeed !');
		}
	}

	public function _refactor($data, $type)
	{
		switch ($type) {
		    case 'Model':
				$search = array(
					'App::uses(\'AppModel\', \'Model\');',
					'\'className\' => \'',
					' extends AppModel {',
				);
				$replace = array(
					'App::uses(\'' . $this->pluginName . 'AppModel\', \'' . $this->pluginName . '.Model\');',
					'\'className\' => \'' . $this->pluginName . '.',
					' extends ' . $this->pluginName . 'AppModel {',
				);
				$data = str_replace($search, $replace, $data);
		        break;
			case 'Controller':
				$search = array(
					'App::uses(\'AppController\', \'Controller\');',
					'Controller extends AppController',
				);
				$replace = array(
					'App::uses(\'' . $this->pluginName . 'AppController\', \'' . $this->pluginName . '.Controller\');',
					'Controller extends ' . $this->pluginName . 'AppController',
				);
				$data = str_replace($search, $replace, $data);
				break;
			case 'View.add':
			case 'View.edit':
			case 'View.index':
			case 'View.view':
				$search = array(
					', array(\'action\' => \'',
					', array(\'controller\' => \'',
				);
				$replace = array(
					', array(\'plugin\' => \'' . $this->pluginUnderscoredName . '\', \'action\' => \'',
					', array(\'plugin\' => \'' . $this->pluginUnderscoredName . '\', \'controller\' => \'',
				);
				$data = str_replace($search, $replace, $data);
				break;
			case 'TestModel':
				$search = array('ClassRegistry::init(\'');
				$replace = array('ClassRegistry::init(\'' . $this->pluginName . '.');
				$data = str_replace($search, $replace, $data);
			case 'TestController':
				$search = array(
					'Controller\', \'Controller\');',
					'\', \'Model\');',
					'\'app.',
				);
				$replace = array(
					'Controller\', \'' . $this->pluginName . '.Controller\');',
					'\', \'' . $this->pluginName . '.Model\');',
					'\'plugin.' . $this->pluginUnderscoredName . '.',
				);
				$data = str_replace($search, $replace, $data);
				break;
			case 'TestFixture':
				break;
			default:
		        break;
		}

		return $data;
	}

}
