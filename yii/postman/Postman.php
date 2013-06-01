<?php
/**
 * Postman.php
 * @author: Roman Revin <xgismox@gmail.com>
 * @date  : 31.05.13
 */

namespace yii\postman;

use Yii;
use PHPMailer;
use yii\base\Component;

/**
 * Class Postman
 * main class for wrap config phpmailer
 * @package yii\postman
 */
class Postman extends Component
{

	/** @var array default value to from */
	public $default_from = array('mailer@localhost', 'Mailer');

	/** @var string db table name for letters */
	public $table = '{{%letter}}';

	/** @var string path to views letters */
	public $view_path = '/email';

	/** @var string driver for sending mail [mail|qmail|sendmail|smtp] */
	public $driver = 'mail';

	/** @var array smtp config */
	public $smtp_config = array(
		'host' => 'localhost',
		'port' => 25,
		'auth' => false,
		'user' => '',
		'password' => '',
		'secure' => 'ssl',
		'debug' => false,
	);

	/** @var PHPMailer object */
	private $_mailer = null;

	/**
	 * method init for component
	 */
	public function init()
	{
		parent::init();

		$from = $this->default_from;

		$mailer = new PHPMailer();
		$mailer->CharSet = 'utf-8';
		$mailer->SetFrom($from[0], $from[1]);

		$this->_mailer = $mailer;

		$this->reconfigure_driver();
		$this->reconfigure_table();
	}

	/**
	 * method adjusts the selected driver to send emails
	 * @return $this
	 * @throws PostmanException
	 */
	public function reconfigure_driver()
	{
		$mailer = $this->_mailer;

		switch ($this->driver) {
			case 'mail':
				$mailer->IsMail();
				break;
			case 'qmail':
				$mailer->IsQmail();
				break;
			case 'sendmail':
				$mailer->IsSendmail();
				break;
			case 'smtp':
				$mailer->IsSMTP();
				$mailer->Host = $this->smtp_config['host'];
				$mailer->Port = $this->smtp_config['port'];
				$mailer->SMTPAuth = $this->smtp_config['auth'];
				$mailer->Username = $this->smtp_config['user'];
				$mailer->Password = $this->smtp_config['password'];
				$mailer->SMTPSecure = $this->smtp_config['secure'];
				$mailer->SMTPDebug = $this->smtp_config['debug'];
				break;
			default:
				throw new PostmanException(Yii::t('app', 'Could not determine the driver is sending letters.'));
		}

		return $this;
	}

	public function reconfigure_table()
	{
		$data = Yii::$app->getDb()->getTableSchema($this->table);
		if ($data === null) {
			$Schema = Yii::$app->getDb()->getSchema();

			Yii::$app->getDb()->createCommand()->createTable(
				$this->table,
				array(
					'id' => $Schema::TYPE_PK,
					'date_create' => $Schema::TYPE_DATETIME,
					'date_send' => $Schema::TYPE_DATETIME,
					'subject' => $Schema::TYPE_STRING,
					'body' => $Schema::TYPE_TEXT,
					'alt_body' => $Schema::TYPE_TEXT,
					'recipients' => $Schema::TYPE_TEXT,
					'attachments' => $Schema::TYPE_TEXT,
				),
				'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB'
			)->execute();
		}
	}

	/**
	 * factory method to create clones of "Postman"
	 * @return PHPMailer
	 */
	public function get_clone_mailer_object()
	{
		return clone $this->_mailer;
	}
}