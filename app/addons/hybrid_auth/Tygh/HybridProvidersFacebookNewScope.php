<?php
namespace Tygh;

class HybridProvidersFacebookNewScope extends \Hybrid_Providers_Facebook
{
    public $scope = 'email, user_about_me, user_birthday, user_hometown, user_website, publish_actions, read_custom_friendlists';
}
?>
