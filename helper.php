<?PHP

/**
 * Hiding IP address plugin
 * Avoid IP addresses shown to public.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     HokkaidoPerson <dosankomali@yahoo.co.jp>
 */

if(!defined('DOKU_INC')) die();


class helper_plugin_hidingip extends DokuWiki_Plugin {

    /**
     * Return alternative text to be shown instead of IPs.
     * It uses lang files of Hidingip plugin, so you needn't set up lang files of your plugin for this function.
     *
     * "$hidingip->altText()" is same to "$hidingip->getLang('notloggedin')"
     *
     * @return string
     */
    public function altText(){
        return $this->getLang('notloggedin');
    }

}
