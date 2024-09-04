<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class PlgContentSc_Guild_Members extends CMSPlugin
{
  public function onContentPrepare($context, &$article, &$params, $limitstart)
  {
    $pattern = '/{guild_members\s*([^}\s]*)\s*(pt|en)?}/i';

    if (preg_match($pattern, $article->text, $matches)) {
      $sid = !empty($matches[1]) ? $matches[1] : 'ALPT';
      $lang = !empty($matches[2]) ? $matches[2] : 'pt';

      $guildMembersHtml = $this->getGuildMembersTable($sid, $lang);
      $article->text = preg_replace($pattern, $guildMembersHtml, $article->text);
    }
  }

  private function getGuildMembersTable($sid, $lang)
  {
    $api_key = 'oQ6KsYj3iNrrSmKPtaL8yHwKCpkMke4k';
    $base_url_members = 'https://api.starcitizen-api.com/' . $api_key . '/v1/live/organization_members/';
    $base_url_user = 'https://api.starcitizen-api.com/' . $api_key . '/v1/live/user/';

    $input = Factory::getApplication()->input;
    $page = $input->getInt('page', 1);
    $search = $input->getString('search', '');

    $labels = [
      'pt' => [
        'location' => 'Localização',
        'fluency' => 'Fluente',
        'enlisted' => 'Alistou-se',
        'rank' => 'Rank',
        'nickname' => 'Apelido',
        'badge' => 'Divisa',
        'stars' => 'Estrelas',
        'roles' => 'Roles',
        'search_placeholder' => 'Pesquisar por nome',
        'search_button' => 'Pesquisar',
        'previous_page' => 'Página Anterior',
        'next_page' => 'Próxima Página',
      ],
      'en' => [
        'location' => 'Location',
        'fluency' => 'Fluent',
        'enlisted' => 'Enlisted',
        'rank' => 'Rank',
        'nickname' => 'Nickname',
        'badge' => 'Badge',
        'stars' => 'Stars',
        'roles' => 'Roles',
        'search_placeholder' => 'Search by name',
        'search_button' => 'Search',
        'previous_page' => 'Previous Page',
        'next_page' => 'Next Page',
      ],
    ];

    if (!empty($search)) {
      $user_url = $base_url_user . urlencode($search) . '?apikey=' . $api_key;
      $user_data = $this->fetchFromAPI($user_url);

      if ($user_data['success'] == 1) {
        $profile = $user_data['data']['profile'];
        $localizacao = is_array($profile['location'])
          ? $labels[$lang]['location'] . ": " . htmlspecialchars(implode(', ', $profile['location']))
          : $labels[$lang]['location'] . ": " . htmlspecialchars($profile['location']);
        $fluente = is_array($profile['fluency'])
          ? $labels[$lang]['fluency'] . ": " . htmlspecialchars(implode(', ', $profile['fluency']))
          : $labels[$lang]['fluency'] . ": " . htmlspecialchars($profile['fluency']);
        return "
                    <div class='card mb-4'>
                        <img src='" . htmlspecialchars($profile['image']) . "' class='card-img-top' alt='" . htmlspecialchars($profile['display']) . "'>
                        <div class='card-body'>
                            <h5 class='card-title'><a href='" . htmlspecialchars($profile['page']['url']) . "' target='_blank'>" . htmlspecialchars($profile['display']) . "</a></h5>
                            <p class='card-text'>
                                " . $labels[$lang]['nickname'] . ": " . htmlspecialchars($profile['handle']) . "<br>
                                " . $labels[$lang]['enlisted'] . ": " . htmlspecialchars($profile['enlisted']) . "<br>
                                " . $labels[$lang]['badge'] . ": " . htmlspecialchars($profile['badge']) . "<br>
                                " . $fluente . "<br>
                                " . $localizacao . "
                            </p>
                        </div>
                    </div>";
      } else {
        return "<div class='alert alert-danger'>Erro ao obter informações do usuário. Verifique se o nome está correto.</div>";
      }
    } else {
      $members_url = $base_url_members . $sid . '?apikey=' . $api_key . '&page=' . $page;
      $members_data = $this->fetchFromAPI($members_url);

      if ($members_data['success'] == 1) {
        $html = "
                    <div class='container-sm mt-3'>
                        <form method='GET' action='' class='mb-4'>
                            <input type='hidden' name='sid' value='" . htmlspecialchars($sid) . "'>
                            <div class='input-group'>
                                <input type='text' class='form-control' name='search' placeholder='" . htmlspecialchars($labels[$lang]['search_placeholder']) . "' value='" . htmlspecialchars($search) . "'>
                                <div class='input-group-append'>
                                    <button class='btn btn-primary' type='submit'>" . htmlspecialchars($labels[$lang]['search_button']) . "</button>
                                </div>
                            </div>
                        </form>
                        <div class='row'>";

        foreach ($members_data['data'] as $member) {
          $current_path = htmlspecialchars(JUri::getInstance()->toString(array('path')));
          $html .= "
                        <div class='col-md-4'>
                            <div class='card mb-4'>
                                <img src='" . htmlspecialchars($member['image']) . "' class='card-img-top' alt='" . htmlspecialchars($member['display']) . "'>
                                <div class='card-body'>
                                    <h5 class='card-title'><a href='" . $current_path . "?sid=ALPT&search=" . htmlspecialchars($member['handle']) . "'>" . htmlspecialchars($member['display']) . "</a></h5>
                                    <p class='card-text'>
                                        " . $labels[$lang]['nickname'] . ": " . htmlspecialchars($member['handle']) . "<br>
                                        " . $labels[$lang]['rank'] . ": " . htmlspecialchars($member['rank']) . "<br>
                                        <span class='input-group'>" . $labels[$lang]['stars'] . ": ";
          for ($i = 0; $i < htmlspecialchars($member['stars']); $i++) {
            $html .= "<img style=\"height: 16px!important; width: 16px!important; margin-top: 5px;\" src=\"https://i.imgur.com/bZtfvUo.png\" />";
          }

          $html .= "</span>
                                        " . $labels[$lang]['roles'] . ": " . htmlspecialchars(implode(', ', $member['roles'])) . "
                                    </p>
                                </div>
                            </div>
                        </div>";
        }

        $html .= "</div>";

        $html .= '<nav>';
        $html .= '<ul class="pagination justify-content-center">';

        $current_path = htmlspecialchars(JUri::getInstance()->toString(array('path')));

        if ($page > 1) {
          $html .= '<li class="page-item"><a class="page-link" href="' . $current_path . '?sid=' . htmlspecialchars($sid) . '&page=' . ($page - 1) . '">' . $labels[$lang]['previous_page'] . '</a></li>';
        }

        $html .= '<li class="page-item"><a class="page-link" href="' . $current_path . '?sid=' . htmlspecialchars($sid) . '&page=' . ($page + 1) . '">' . $labels[$lang]['next_page'] . '</a></li>';

        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';

        return $html;
      } else {
        return "<div class='alert alert-danger'>Erro ao obter os membros da organização. Verifique se o SID está correto.</div>";
      }
    }
  }

  private function fetchFromAPI($url)
  {
    $response = file_get_contents($url);
    return json_decode($response, true);
  }
}
