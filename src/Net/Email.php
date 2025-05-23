<?php

namespace BlueFission\Net;

use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\HTML\HTML;
use BlueFission\DataTypes;
use BlueFission\Obj;
use BlueFission\Val;
use BlueFission\Num;
use BlueFission\Str;
use BlueFission\Arr;

/**
 * Class Email
 *
 * @package BlueFission\Net
 */
class Email extends Obj implements IConfigurable, IEmail
{
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    /**
     * An array that stores the email configurations.
     *
     * @var array
     */
    protected $_config = [
        'sender' => '',
        'html' => false,
        'eol' => "\r\n",
    ];

    /**
     * An array that stores the email headers.
     *
     * @var array
     */
    private $_headers = [];

    /**
     * An array that stores the email attachments.
     *
     * @var array
     */
    private $_attachments = [];

    /**
     * An array that stores the email recipients.
     *
     * @var array
     */
    private $_recipients = [];

    /**
     * A constant to represent default recipients.
     *
     * @var string
     */
    public const TO = 'default';

    /**
     * A constant to represent CC recipients.
     *
     * @var string
     */
    public const CC = 'cc';

    /**
     * A constant to represent BCC recipients.
     *
     * @var string
     */
    public const BCC = 'bcc';

    /**
     * An array that stores the email data such as 'from', 'message', 'subject'.
     *
     * @var array
     */
    protected $_data = [
        'from' => '',
        'message' => '',
        'subject' => '',
        'additional' => ''
    ];

    protected $_types = [
        'from' => DataTypes::STRING,
        'message' => DataTypes::STRING,
        'subject' => DataTypes::STRING,
        'additional' => DataTypes::STRING
    ];

    /**
     * Constructor function to initialize email data such as recipients, from, subject, message, cc, bcc, html, headers_r, additional and attachments.
     *
     * @param string $recipient
     * @param string $from
     * @param string $subject
     * @param string $message
     * @param string $cc
     * @param string $bcc
     * @param bool $html
     * @param string $headers_r
     * @param string $additional
     * @param string $attachments
     */
    public function __construct($recipient = null, $from = null, $subject = null, $message = null, $cc = null, $bcc = null, $html = false, $headers_r = null, $additional = null, $attachments = null)
    {
        $this->__configConstruct();
        parent::__construct();

        $recipient = Arr::toArray($recipient);
        $cc = Arr::toArray($cc);
        $bcc = Arr::toArray($bcc);

        //Prepare addresses
        $this->recipients($recipient);
        $this->recipients($cc, null, self::CC);
        $this->recipients($bcc, null, self::BCC);
        $this->from($from);
        $this->subject($subject);
        $this->body($message);
        $this->additional($additional);
        $this->headers($headers_r);
    }

    /**
     * A function to set or get the value of an email data field.
     *
     * @param string $field
     * @param null $value
     * @return mixed
     */
    public function field(string $field, $value = null): mixed
    {
        if (!$this->_data->hasKey($field)) {
            return null;
        }

        if (Val::isNotNull($value)) {
            $this->_data[$field] = $value;

            return $this;
        } else {
            $value = (isset($this->_data[$field])) ? $this->_data[$field] : null;
        }

        return $value;
    }

    /**
     * headers - retrieves or sets the headers
     *
     * @param mixed $input  the header name or an array of headers to set
     * @param mixed $value  the value of the header
     *
     * @return mixed the headers if $input is not provided, the value of the header if $value is not provided, or null if the header does not exist
     */
    public function headers($input = null, $value = null)
    {
        if (Str::is($input)) {
            if (Val::isNull($value)) {
                return isset($this->_headers[$input]) ? $this->_headers[$input] : false;
            }

            $this->_headers[$input] = self::sanitize($value);

            return $this;
        } elseif (Arr::is($input)) {
            foreach ($input as $a => $b) {
                $this->_headers[self::sanitize($a)] = self::sanitize($b);
            }

            return $this;
        }

        if (Val::isNull($input)) {
            return $this->_headers;
        }
    }

    /**
     * attach - retrieves or sets the attachments
     *
     * @param mixed $input  the attachment name or an array of attachments to set
     * @param mixed $value  the value of the attachment
     *
     * @return mixed the attachments if $input is not provided, the value of the attachment if $value is not provided, or null if the attachment does not exist
     */
    public function attach($input = null, $value = null)
    {
        if (Str::is($input)) {
            if (Val::isNull($value)) {
                return isset($this->_attachments[$input]) ? $this->_attachments[$input] : null;
            }

            $this->_attachments[$input] = $value;

            return $this;
        } elseif (Arr::is($input)) {
            foreach ($input as $a => $b) {
                $this->_attachments[$a] = $b;
            }

            return $this;
        }

        if (Val::isNull($input)) {
            return $this->_attachments;
        }
    }

    /**
     * recipients - retrieves or sets the recipients
     *
     * @param mixed $value  the value of the recipient
     * @param mixed $name   the name of the recipient
     * @param mixed $type   the type of the recipient
     *
     * @return mixed the recipients if $value is not provided, or the value of the recipient if it exists, or an empty array if it does not
     */
    public function recipients($value = null, $name = null, $type = null)
    {
        if (Val::isNull($value)) {
            return $this->_recipients;
        }

        if (!Arr::is($value)) {
            $value = [$value];
        }

        $type = $type ?? self::TO;

        $value = self::filterAddresses($value);

        if (Val::isNotNull($name)) {
            $value = [$value[0] => $name];
        }

        if (!$value) {
            return $this;
        }

        $this->_recipients[$type] = $this->_recipients[$type] ?? [];

        $this->_recipients[$type] = (Val::is($this->_recipients[$type]) && Arr::size($this->_recipients[$type]) > 0)
            ? Arr::merge($this->_recipients[$type], $value) : $value;

        $this->_recipients[$type] = Arr::iUnique($this->_recipients[$type]);

        return $this;
    }

    /**
     *
     * Get recipients based on the type provided or default type.
     *
     * @param string|null $type Type of recipients to get
     *
     * @return array Array of recipients
     */
    public function getRecipients($type = null)
    {
        $type = $type ?? Email::TO;

        $recipients = $this->_recipients[$type] ?? [];

        $isAssoc = Arr::isAssoc($recipients);

        foreach ($recipients as $a => $b) {
            if (Num::is($a)) {
                $recipients[$b] = $b;
            } else {
                $recipients[$a] = "{$b} <{$a}>";
            }
        }

        $recipients = Arr::iUnique($recipients);

        return $recipients;
    }

    /**
     * Set the 'From' field of the email.
     *
     * @param string|null $value Email address to set as the 'From' field
     * @param string|null $name Name to set as the 'From' field
     *
     * @return mixed
     */
    public function from($value = null, $name = null)
    {
        if (Val::isNull($value)) {
            return $this->field('from') ? $this->field('from') : $this->config('sender');
        }

        if ((Val::isNotNull($value)) && !self::validateAddress($value)) {
            return $this;
        }

        if (!Arr::is($value) && Val::isNotNull($name)) {
            $value = [$name => $value];
        }

        $this->field('from', $value);

        return $this;
    }

    /**
     * Set the message content of the email.
     *
     * @param string|null $value Message content for the email
     *
     * @return mixes The sanitized message content
     */
    public function body($value = null)
    {
        if (Val::isNull($value)) {
            return $this->field('message');
        }

        $value = self::sanitize($value);
        $this->field('message', $value);

        return $this;
    }

    /**
     * Set the subject of the email.
     *
     * @param string|null $value Subject of the email
     *
     * @return mixed
     */
    public function subject($value = null)
    {
        if (Val::isNull($value)) {
            return $this->field('subject');
        }

        $value = self::sanitize($value);
        $this->field('subject', $value);

        return $this;
    }

    /**
     * Set the 'sendHTML' config.
     *
     * @param bool|null $value Boolean value to set the 'sendHTML' config
     *
     * @return mixed
     */
    public function sendHTML($value = null)
    {
        if (Val::isNull($value)) {
            return $this->config('html');
        }

        $this->config('html', $value);

        return $this;
    }

    /**
     * Get the latest status message.
     *
     * @param string|null $message Status message to set
     *
     * @return string The latest status message
     */
    public function status($message = null)
    {
        if (Val::isNull($message)) {
            $message = end($this->_status);
            return $message;
        }

        $this->_status[] = $message;

        return $this;
    }

    /**
     * Validate an email address.
     *
     * @param string|array $address Email address(es) to validate
     *
     * @return bool Returns true if all email addresses are valid, false otherwise
     */
    public static function validateAddress($address = null)
    {
        $address = Arr::toArray($address); //dev_value_to_array($address);
        $p = '/^[a-z0-9!#$%&*+-=?^_`{|}~\.]+([\.\+][a-z0-9!#$%&*+-=?^_`{|}~\.]+)*';
        $p .= '@[a-z0-9][-a-z0-9]*(\.[a-z0-9][-a-z0-9]*)*';
        $p .= '(\.[a-z]{2,}';
        $p .= '|\.xn--[a-z0-9]{2,}';
        $p .= '|\.com|\.net|\.edu|\.org|\.gov|\.mil|\.int|\.biz|\.pro|\.info|\.arpa|\.aero|\.coop|\.name|\.museum|\.au|\.jp|\.tv|\.us|\.nz|\.nt)$/ix';

        $pattern = $p;

        // Regex to get email address from out of  User Name <email@address> format
        $filter = '/(?<=<)?[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}(?=>)?/';

        $passed = false;
        $i = 0;
        $count = count($address);
        do {
            // get email from User Name <email@address> format
            preg_match($filter, $address[$i] ?? '', $matches);
            $email = $matches[0] ?? null;

            $email = $email ?? '';
            $match = preg_match($pattern, $email);
            $passed = ($match > 0 && $match !== false) ? true : false;
            $i++;
        } while ($passed === true && $i < $count);

        return $passed;
    }

    /**
     * Filter out invalid email addresses from an array
     *
     * @param array $addresses The array of email addresses to filter
     * @return array|bool An array of valid email addresses or false if none are found
     */
    public function filterAddresses($addresses = null)
    {
        $address_r = Arr::toArray($addresses);
        $valid_address_r = [];
        foreach ($address_r as $a) {
            if (self::validateAddress($a)) {
                $valid_address_r[] = self::sanitize($a);
            }
        }
        if (count($valid_address_r) == 0) {
            return false;
        }
        return $valid_address_r;
    }

    /**
     * Sanitize a given field
     *
     * @param string $field The field to sanitize
     * @return string The sanitized field
     */
    public static function sanitize($field)
    {
        if (Val::isNull($field)) {
            return null;
        }
        //Remove line feeds
        $ret = str_replace("\r", "", $field);
        // Remove injected headers
        $find = array("/bcc\:/i",
                "/Content\-Type\:/i",
                "/Mime\-Type\:/i",
                "/cc\:/i",
                "/to\:/i");
        $ret = preg_replace($find, "", $field);

        return $ret;
    }

    /**
     * Send an email
     *
     * @return IObj
     */
    public function send()
    {
        $status = 'Failed to send mail. ';
        $from = $this->from();
        $subject = $this->subject();

        $attachments = $this->_attachments;

        $eol = $this->config('eol');
        $mime_boundary = md5(time());

        //Build Headers
        $this->_headers = [];
        if ($this->_attachments) {
            $this->_headers['MIME-Version'] = "1.0";
            $this->_headers['Content-Type'] = "multipart/mixed; boundary=\"mixed-{$mime_boundary}\"";
        } elseif ($this->sendHTML()) {
            $this->_headers['MIME-Version'] = "1.0";
            $this->_headers['Content-Type'] = "multipart/related; boundary=\"mixed-{$mime_boundary}\"";
        } else {
            $this->_headers['Content-Type'] = "text/plain; charset=iso-8859-1";
        }

        if ($from != '' && self::validateAddress($this->from())) {
            $this->_headers['From'] = "{$from}";
            $this->_headers['Reply-To'] = "{$from}";
            $this->_headers['Return-Path'] = "{$from}";
            $this->_headers['Message-ID'] = "<".time()."-{$from}>";
        }

        $recipients = $this->getRecipients();
        $cc = $this->getRecipients(self::CC) ?? [];
        $bcc = $this->getRecipients(self::BCC) ?? [];

        if (count($cc) > 0) {
            $this->_headers["Cc"] = implode(', ', $cc);
        }
        if (count($bcc) > 0) {
            $this->_headers["Bcc"] = implode(', ', $bcc);
        }
        $this->_headers['X-Mailer'] = "PHP/" . phpversion();

        //Compile mail data

        foreach ($this->headers() as $a => $b) {
            $headers = "{$a}: $b";
        }
        $header_info = implode($eol, $this->_headers);
        $message = $this->body();
        $message = wordwrap($message, 70);

        $body = "";

        if ($attachments) {
            foreach ($attachments as $file) {
                if (is_file($file["file"])) {
                    if (file_exists($file["file"])) {
                        $file_name = substr($file["file"], (strrpos($file["file"], "/") + 1));

                        $handle = fopen($file["file"], 'rb');
                        $f_contents = fread($handle, filesize($file["file"]));
                        $f_contents = chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
                        fclose($handle);

                        // Attach
                        $body .= "--mixed-{$mime_boundary}{$eol}";
                        $body .= "Content-Type: {$file["type"]}; name=\"{$file_name}\"{$eol}";
                        $body .= "Content-Transfer-Encoding: base64{$eol}";
                        $body .= "Content-Disposition: attachment; filename=\"{$file_name}\"{$eol}{$eol}"; // !! This line needs TWO end of lines !! IMPORTANT !!
                        $body .= $f_contents.$eol.$eol;
                    }
                }
            }
            $body .= "--mixed-".$mime_boundary.$eol;
        }

        // Begin message text
        if ($this->sendHTML() === true) {
            $body .= "Content-Type: multipart/alternative; boundary: \"alt-{$mime_boundary}\"{$eol}";
            // HTML Text
            $body .= "--alt-".$mime_boundary.$eol;
            $body .= "Content-Type: text/html; charset=iso-8859-1{$eol}";
            $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
            $body .= $message.$eol.$eol;

            // Ready plain text headers
            $body .= "--alt-".$mime_boundary.$eol;
            $body .= "Content-Type: text/plain; charset=iso-8859-1{$eol}";
            $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
        }

        // Plain Text
        $body .= strip_tags(HTML::br2nl($message)).$eol.$eol;

        // Body end
        if ($this->sendHTML()) {
            $body .= "--alt-{$mime_boundary}--{$eol}{$eol}";
        }

        if ($attachments) {
            $body .= "--mixed-{$mime_boundary}--{$eol}{$eol}";
        }  // finish with two eol's for better security. see Injection.


        // the INI lines are to force the From Address to be used
        ini_set("sendmail_from", $this->from());

        if (count($recipients) <= 0) {
            $status .= "The send to address is empty.\n";
        } elseif (!self::validateAddress($this->getRecipients())) {
            $status .= "Email address '" . implode(', ', $this->rcpt) . "' is invalid.\n";
        } elseif ($subject == '') {
            $status .= "Subject line is empty.\n";
        } elseif ($message == '') {
            $status .= "Message is empty.\n";
        } elseif (count($cc) > 0 && !self::validateAddress($cc)) {
            $status .= "Invalid address in the CC line\n";
        } elseif (count($bcc) > 0 && !self::validateAddress($bcc)) {
            $status .= "Invalid address in the BCC line\n";
        } elseif (mail(implode(', ', $this->getRecipients()), $this->subject(), $body, $header_info, $this->field('additional'))) {
            $status = "Mail delivered successfully\n";
        }
        ini_restore("sendmail_from");

        $this->status($status);

        return $this;
    }

    public static function sendMail($to, $from, $subject, $message, $cc = null, $bcc = null, $html = false, $headers_r = null, $additional = null, $attachments = null)
    {
        $mail = new Email($to, $from, $subject, $message, $cc, $bcc, $html, $headers_r, $additional, $attachments);
        $mail->send();
        return $mail->status();
    }

}
