<?php
/**
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

jimport('joomla.environment.browser');

/**
 * Recaptcha Plugin.
 * Based on the official recaptcha library( https://developers.google.com/recaptcha/docs/php ).
 *
 * @since       2.5
 */
class plgCaptchaRecaptchaJ25 extends JPlugin
{
    const RECAPTCHA_API_SERVER = 'http://www.google.com/recaptcha/api';
    const RECAPTCHA_API_SECURE_SERVER = 'https://www.google.com/recaptcha/api';
    const RECAPTCHA_VERIFY_SERVER = 'www.google.com';

    public function __construct($subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    /**
     * Initialise the captcha.
     *
     * @param string $id the id of the field
     *
     * @return bool True on success, false otherwise
     *
     * @since  2.5
     */
    public function onInit($id)
    {
        // Initialise variables
        $lang = $this->_getLanguage();
        $params = $this->params->toArray();

        if (isset($params['data'])) {
            $pubkey = $params['data']['public_key'] ?: '';
            $theme = $params['data']['theme'] ?: 'clean';
        } else {
            $pubkey = $params['public_key'] ?: '';
            $theme = $params['theme'] ?: 'clean';
        }

        if (empty($pubkey)) {
            throw new Exception(JText::_('PLG_RECAPTCHA_ERROR_NO_PUBLIC_KEY'));
        }

        $server = self::RECAPTCHA_API_SERVER;
        if (JBrowser::getInstance()->isSSLConnection()) {
            $server = self::RECAPTCHA_API_SECURE_SERVER;
        }

        JHtml::_('script', $server.'.js?hl='.$lang);

        return true;
    }

    /**
     * Gets the challenge HTML.
     *
     * @return string the HTML to be embedded in the form
     *
     * @since  2.5
     */
    public function onDisplay($name, $id, $class)
    {
        $params = $this->params->toArray();

        if (isset($params['data'])) {
            $publicKey = $params['data']['public_key'] ?: '';
            $theme = $params['data']['theme'] ?: 'clean';
        } else {
            $publicKey = $params['public_key'] ?: '';
            $theme = $params['theme'] ?: 'clean';
        }

        return '<div class="g-recaptcha" data-sitekey="'.$publicKey.'" data-theme="'.$theme.'"></div>';
    }

    /**
     * Calls an HTTP POST function to verify if the user's guess was correct.
     *
     * @return true if the answer is correct, false otherwise
     *
     * @since  2.5
     */
    public function onCheckAnswer($code)
    {
        require_once dirname(__FILE__).'/vendor/autoload.php';

        // Initialise variables
        $privateKey = $this->params->get('private_key');

        // Check for Private Key
        if (empty($privateKey)) {
            $this->_subject->setError(JText::_('PLG_RECAPTCHA_ERROR_NO_PRIVATE_KEY'));

            return false;
        }

        try {
            $recaptcha = new \ReCaptcha\ReCaptcha($privateKey);

            $gRecaptchaResponse = JRequest::getVar('g-recaptcha-response');
            $remoteAddr = JRequest::getVar('REMOTE_ADDR', '127.0.0.1', 'SERVER');

            $resp = $recaptcha->verify($gRecaptchaResponse, $remoteAddr);

            if (!$resp->isSuccess()) {
                $this->_subject->setError('Captcha mal ingresado, vuelva a ingresar las letras de la imagen correctamente. ');

                return false;
            }
        } catch (Exception $e) {
            $this->_subject->setError($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Get the language tag or a custom translation.
     *
     * @return string
     *
     * @since  2.5
     */
    private function _getLanguage()
    {
        // Initialise variables
        $language = JFactory::getLanguage();

        $tag = explode('-', $language->getTag());
        $tag = $tag[0];
        $available = array('en', 'pt', 'fr', 'de', 'nl', 'ru', 'es', 'tr');

        if (in_array($tag, $available)) {
            return "lang : '".$tag."',";
        }

        // If the default language is not available, let's search for a custom translation
        if ($language->hasKey('PLG_RECAPTCHA_CUSTOM_LANG')) {
            $custom[] = 'custom_translations : {';
            $custom[] = "\t".'instructions_visual : "'.JText::_('PLG_RECAPTCHA_INSTRUCTIONS_VISUAL').'",';
            $custom[] = "\t".'instructions_audio : "'.JText::_('PLG_RECAPTCHA_INSTRUCTIONS_AUDIO').'",';
            $custom[] = "\t".'play_again : "'.JText::_('PLG_RECAPTCHA_PLAY_AGAIN').'",';
            $custom[] = "\t".'cant_hear_this : "'.JText::_('PLG_RECAPTCHA_CANT_HEAR_THIS').'",';
            $custom[] = "\t".'visual_challenge : "'.JText::_('PLG_RECAPTCHA_VISUAL_CHALLENGE').'",';
            $custom[] = "\t".'audio_challenge : "'.JText::_('PLG_RECAPTCHA_AUDIO_CHALLENGE').'",';
            $custom[] = "\t".'refresh_btn : "'.JText::_('PLG_RECAPTCHA_REFRESH_BTN').'",';
            $custom[] = "\t".'help_btn : "'.JText::_('PLG_RECAPTCHA_HELP_BTN').'",';
            $custom[] = "\t".'incorrect_try_again : "'.JText::_('PLG_RECAPTCHA_INCORRECT_TRY_AGAIN').'",';
            $custom[] = '},';
            $custom[] = "lang : '".$tag."',";

            return implode("\n", $custom);
        }

        // If nothing helps fall back to english
        return '';
    }
}
