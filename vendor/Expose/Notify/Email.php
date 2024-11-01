<?php

namespace Expose\Notify;

class Email extends \Expose\Notify
{
    /**
     * To email address for notifications
     * @var string
     */
    private $toAddress = null;

    /**
     * From address for notifications
     * @var string
     */
    private $fromAddress = 'notify@expose';
	
    /**
     * Additional information to be included on email
     * @var string
     */
	private $additionalInfo = '';
	
    /**
     * Init the object and set to/from addresses if given
     * 
     * @param string $toAddress "To" email address
     * @param string $fromAddress "From" email address
     */
    public function __construct($toAddress = null, $fromAddress = null)
    {
        if ($toAddress !== null) {
            $this->setToAddress($toAddress);
        }
        if ($fromAddress !== null) {
            $this->setFromAddress($fromAddress);
        }
    }

    /**
     * Set the "To" address for the notification
     *
     * @param string $emailAddress Email address
     */
    public function setToAddress($emailAddress)
    {
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== $emailAddress) {
            throw new \InvalidArgumentException('Invalid email address: '.$emailAddress);
        }
        $this->toAddress = $emailAddress;
    }

    /**
     * Get the current "To" address for notification
     *
     * @return string Email address
     */
    public function getToAddress()
    {
        return $this->toAddress;
    }

    /**
     * Set the current "From" email address on notifications
     * 
     * @param string $emailAddress Email address
     */
    public function setFromAddress($emailAddress)
    {
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== $emailAddress) {
            throw new \InvalidArgumentException('Invalid email address: '.$emailAddress);
        }
        $this->fromAddress = $emailAddress;
    }

    /**
     * Return the current "From" address setting
     * 
     * @return string Email address
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }
	
	
	public function setAdditionalInfo($info)
	{
		$this->additionalInfo = $info;
	}
	
    /**
     * Send the notification to the given email address
     * 
     * @param array $filterMatches Set of filter matches from execution
     * @return boolean Success/fail of sending email
     */
    public function send($reportGrouped)
    {
        $toAddress = $this->getToAddress();
        $fromAddress = $this->getFromAddress();
        $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        
        if ($toAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "to" email address');
        }

        if ($fromAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "from" email address');
        }
        
        $emailBody = '<html>
	<body>
		Expose executed at '.date('r').' and found the following matches in the submitted data from '.$ip.' ' 
		. ((!empty($this->additionalInfo)) ? $this->additionalInfo : '')
		. '<br/><br/>'
		. '<table cellspacing="0" cellpadding="3" border="0">
		<tr>
            <td><b>Variable</b></td>
            <td><b>Value</b></td>
            <td><b>Impact</b></td>
            <td><b>Tags</b></td>
            <td><b>Description</b></td>
        </tr>';
        $headers = array(
            "From: ".$fromAddress,
            "Content-type: text/html; charset=iso-8859-1"
        );
        $totalImpact = 0;
        
        $impactData = array();
        foreach ($reportGrouped as $c=>$v) {
            foreach ( $v['filters'] as $filter) {
                $emailBody .= '<tr>
                            <td align="center">'.$c.'</td>
                            <td align="center">'.htmlentities($v['value']).'</td>
                            <td align="center">'.$filter->getImpact().'</td>
                            <td align="center">'.implode(', ', $filter->getTags()).'</td>
                            <td>'.$filter->getDescription().' ('.$filter->getId().')</td>
                        </tr>';
                $totalImpact += $filter->getImpact();
            }
        }
        $emailBody .= '</table>
                <p>
                    <b>Total Impact Score:</b> '.$totalImpact.'</p>
            </body>
        </html>';

        $subject = 'Expose Notification - Impact Score '.$totalImpact;
        return @mail($toAddress, $subject, $emailBody, implode("\r\n", $headers));
    }
    
    /**
     * Send the notification to the given email address using Twig
     * 
     * @param array $filterMatches Set of filter matches from execution
     * @return boolean Success/fail of sending email
     */
    public function sendWithTwig($reportGrouped)
    {
        $toAddress = $this->getToAddress();
        $fromAddress = $this->getFromAddress();

        if ($toAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "to" email address');
        }

        if ($fromAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "from" email address');
        }

        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../Template');
        $twig = new \Twig_Environment($loader);
        $template = $twig->loadTemplate('Notify/EmailGrouped.twig');

        $headers = array(
            "From: ".$fromAddress,
            "Content-type: text/html; charset=iso-8859-1"
        );
        $totalImpact = 0;
        
        
        $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        
        $impactData = array();
        foreach ($reportGrouped as $c=>$v) {
            foreach ( $v['filters'] as $filter) {
                $impactData[] = array(
                    'var' => $c,
                    'value' => $v['value'],
                    'impact' => $filter->getImpact(),
                    'description' => $filter->getDescription(),
                    'id' => $filter->getId(),
                    'tags' => implode(', ', $filter->getTags())
                );
                $totalImpact += $filter->getImpact();
            }
        }

        $subject = 'Expose Notification - Impact Score '.$totalImpact;
        $body = $template->render(array(
            'impactData' => $impactData,
            'runTime' => date('r'),
            'totalImpact' => $totalImpact,
            'ip' => $ip
        ));

        return @mail($toAddress, $subject, $body, implode("\r\n", $headers));
    }
    
    public function _sendOriginal($filterMatches)
    {
        $toAddress = $this->getToAddress();
        $fromAddress = $this->getFromAddress();

        if ($toAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "to" email address');
        }

        if ($fromAddress === null) {
            throw new \InvalidArgumentExcepion('Invalid "from" email address');
        }

        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../Template');
        $twig = new \Twig_Environment($loader);
        $template = $twig->loadTemplate('Notify/Email.twig');

        $headers = array(
            "From: ".$fromAddress,
            "Content-type: text/html; charset=iso-8859-1"
        );
        $totalImpact = 0;

        $impactData = array();
        foreach ($filterMatches as $match) {
            $impactData[] = array(
                'impact' => $match->getImpact(),
                'description' => $match->getDescription(),
                'id' => $match->getId(),
                'tags' => implode(', ', $match->getTags())
            );
            $totalImpact += $match->getImpact();
        }

        $subject = 'Expose Notification - Impact Score '.$totalImpact;
        $body = $template->render(array(
            'impactData' => $impactData,
            'runTime' => date('r'),
            'totalImpact' => $totalImpact
        ));

        return mail($toAddress, $subject, $body, implode("\r\n", $headers));
    }
}
