<?php
/**
 * @package libs.helper
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class CustomHTMLHelper extends Delta_HTMLHelper
{
  private $_site = array();

  public function initialize()
  {
    $this->_site = Delta_Config::getSite();
  }

  public function getBloodName($blood)
  {
    return $this->_site->get('blood')->get($blood);
  }

  public function getHobbyNameList($hobbies)
  {
    if (is_object($hobbies)) {
      $hobbies = $hobbies->getRaw();
      $hobbyNames = array();

      if (is_array($hobbies)) {
        foreach ($hobbies as $hobby) {
          $hobbyNames[] = $this->_site->get('hobby')->get($hobby);
        }

      } else {
        foreach ($this->_site->get('hobby') as $id => $name) {
          if ($id & $hobbies) {
            $hobbyNames[] = $name;
          }
        }
      }

      return $this->ul($hobbyNames);
    }
  }

  public function memberIconImage($memberId)
  {
    $member = $this->getService('Member');
    $path = $member->getIconPath($memberId);

    if (is_file($path)) {
      $path = substr($path, strpos($path, '/assets'));

      $array = array();
      $array['class'] = 'border';
      $array['alt'] = 'アイコン';

      return $this->image($path, $array);
    }

    return NULL;
  }
}
