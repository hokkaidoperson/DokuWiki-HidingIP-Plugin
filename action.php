<?PHP

/**
 * Hiding IP address plugin
 * Avoid IP addresses shown to public.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     HokkaidoPerson <dosankomali@yahoo.co.jp>
 */

if(!defined('DOKU_INC')) die();


class action_plugin_hidingip extends DokuWiki_Action_Plugin {

    /**
     * Run this plugin in:
     * 1. Recent Changes
     * 2. Old Revisions
     * 3. Last Modified
     * 4. Page Locking
     * 5. Showing Diff
     */
    public function register(Doku_Event_Handler $controller) {
        $whentohide = explode(',', $this->getConf('whenToHide'));
        $whentohide = array_map('trim', $whentohide);
        $whentohide = array_unique($whentohide);
        $whentohide = array_filter($whentohide);

        if (array_search('recent', $whentohide) !== FALSE) $controller->register_hook('HTML_RECENTFORM_OUTPUT', 'BEFORE', $this, 'recentform', array());
        if (array_search('revision', $whentohide) !== FALSE) $controller->register_hook('HTML_REVISIONSFORM_OUTPUT', 'BEFORE', $this, 'revisionform', array());
        if (array_search('diff', $whentohide) !== FALSE) $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'tplcontent', array());
        if (array_search('userlink', $whentohide) !== FALSE ) $controller->register_hook('COMMON_USER_LINK', 'BEFORE', $this, 'userlink', array());
    }

    /**
     * In recent changes
     */
    public function recentform(Doku_Event $event, $param) {

        $display = $this->getLang('notloggedin');
        $flag = FALSE;
        $showip = FALSE;

        // Allowed to see IPs?
        if ($this->getConf('rightToSeeIP') == 'mg' && auth_ismanager()) $showip = TRUE;
        if ($this->getConf('rightToSeeIP') == 'sp' && auth_isadmin()) $showip = TRUE;

        // Reminder / メモ書き (en, ja)
        //
        // When $event->data->_content['(number)']['class'] has 'user', there is an user name or an IP address in $event->data->_content['(the next number)'].
        // Thus this plugin detects the 'user' texts, and substitues TRUE for $flag if found.
        // If the $flag is TRUE, this plugin will check whether or not there is an IP in the texts.  If found, it'll be hidden (but admins can view it even with this plugin).
        //
        // $event->data->_content['(任意の数字)']['class']に文字列'user'があった場合、$event->data->_content['(その次の数字)']には、ユーザー名もしくはIPアドレスが保持されています。
        // なので、このプラグインではその'user'文字列を検出し、見付かった場合、$flag変数にTRUEを代入します。
        // その$flag変数がTRUEの場合に、文字列内にIPアドレスがあるかチェックします。もしあれば、それを非表示にします（但し、管理人はこのプラグインの介入に関わらずIPアドレスを閲覧出来ます）。

        foreach ($event->data->_content as $key => $ref) {
            if ($flag == TRUE and strpos($ref,'<bdo dir="ltr">') !== FALSE) {
                if ($showip == TRUE) {
                    $event->data->_content[$key] = '<bdi>' . $display . '</bdi> <bdo dir="ltr">(' . substr($ref, strlen('<bdo dir="ltr">'), -6) . ')</bdo>';
                } else {
                    $event->data->_content[$key] = '<bdi>' . $display . '</bdi>';
                }
            }

            $flag = FALSE;

            if (is_array($ref)) {
                if (array_key_exists('class', $ref)) {
                    if ($ref['class'] == 'user') $flag = TRUE;
                }
            }
        }
    }

    /**
     * In old revisions
     */
    public function revisionform(Doku_Event $event, $param) {

        $display = $this->getLang('notloggedin');
        $flag = FALSE;
        $showip = FALSE;

        // Allowed to see IPs?
        if ($this->getConf('rightToSeeIP') == 'mg' && auth_ismanager()) $showip = TRUE;
        if ($this->getConf('rightToSeeIP') == 'sp' && auth_isadmin()) $showip = TRUE;

        // Reminder / メモ書き (en, ja)
        //
        // The function is very similar to that in recent changes.
        // But, the first item of the old revision is little different from others, so there is some customizing.
        //
        // 「最近の更新」とほぼ同じ機能ですが、以前のリビジョンの最初の項目が他の項目と少し異なるようなので、それに合わせたカスタマイズをしています。

        foreach ($event->data->_content as $key => $ref) {
            if ($flag == TRUE and strpos($ref,'<bdo dir="ltr">') !== FALSE) {
                if ($showip == TRUE) {
                    $event->data->_content[$key] = '<bdi>' . $display . '</bdi> <bdo dir="ltr">(' . substr($ref, strlen('<bdo dir="ltr">'), -6) . ')</bdo>';
                } else {
                    $event->data->_content[$key] = '<bdi>' . $display . '</bdi>';
                }
            } else if ($flag == TRUE and preg_match('/<bdi>(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])<\/bdi>/' , $ref) == 1) {
               $event->data->_content[$key] = '<bdi>' . $display . '</bdi>';
            }

            $flag = FALSE;

            if (is_array($ref)) {
                if (array_key_exists('class', $ref)) {
                    if ($ref['class'] == 'user') $flag = TRUE;
                }
            }
        }
    }

    /**
     * In showing diffs
     */
    public function tplcontent(Doku_Event $event, $param) {

        // Keep out if not managing diffs
        global $INPUT;
        if ($INPUT->str('do') != 'diff') return;


        $display = $this->getLang('notloggedin');
        $showip = FALSE;

        // Allowed to see IPs?
        if ($this->getConf('rightToSeeIP') == 'mg' && auth_ismanager()) $showip = TRUE;
        if ($this->getConf('rightToSeeIP') == 'sp' && auth_isadmin()) $showip = TRUE;

        // Reminder / メモ書き (en, ja)
        //
        // The function is similar to the functions above, but it'll modify HTML directly.
        //
        // 上2つと似たような機能ですが、ここではHTMLを直接いじっています。

        $ref = $event->data;

        if (strpos($ref,'<span class="user"><bdo dir="ltr">') !== FALSE) {
            if ($showip == TRUE) {
                $event->data = preg_replace('/<span class="user"><bdo dir="ltr">((([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))<\/bdo>/', '<span class="user"><bdi>' . $display . '</bdi> <bdo dir="ltr">($1)</bdo>', $ref);
            } else {
                $event->data = preg_replace('/<span class="user"><bdo dir="ltr">(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])<\/bdo>/', '<span class="user"><bdi>' . $display . '</bdi>', $ref);
            }
        }
    }

    /**
     * Modifying user datas (Last Modified and Page Locking)
     */
    public function userlink(Doku_Event $event, $param) {

        $display = $this->getLang('notloggedin');

        // Reminder / メモ書き (en, ja)
        //
        // If $event->data['username'] is likely to be an IP, the plugin will write $event->data['name'].
        // You can't use the user name like IPs (that'll be accidentally replaced. e.g.: 3.57.2.13 ).
        //
        // $event->data['username']がIPアドレスと思われる場合、$event->data['name']を書き込みます。
        // IPアドレスっぽいユーザー名だった場合、ログイン中であってもうっかり置き換えられてしまいます。
        // 　　例：3.57.2.13


        if (preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/' , $event->data['username']) == 1) $event->data['name'] = $display;
    }


}
