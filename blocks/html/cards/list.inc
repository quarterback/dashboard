<?php
/**
 * @copyright 2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @param array $this->cards
 */
use Application\Models\Person;

$this->template->addToAsset('scripts', BASE_URI.'/js/cards/list.js');
?>
<section>
    <header>
        <h1><?= $this->_(['card', 'cards', count($this->cards)]); ?></h1>
        <div class="tools">
        <?php
            $helper = $this->template->getHelper('buttonLink');

            if (Person::isAllowed('cards', 'add')) {
                echo $helper->buttonLink(
                    self::generateUri('cards.update'),
                    $this->_('card_add'),
                    'add'
                );

            }
        ?>
        </div>
    </header>
    <table>
    <?php
        foreach ($this->cards as $c) {
            $id          = $c->getId();
            $description = parent::escape($c->getDescription());
            $service     = parent::escape($c->getService()->getName());
            $uri         = parent::generateUri('cards.view', ['id'=>$id]);
            $target      = parent::escape($c->getTarget());
            $responseKey = parent::escape($c->getResponseKey());
            $comparison  = $this->_($c->getComparison());
            $parameters  = json_encode($c->getParameters());
            echo "
            <tr><th>$id</th>
                <td><a href=\"$uri\">$description</a></td>
                <td>$service</td>
                <td>{$c->getMethod()}</td>
                <td>$responseKey</td>
                <td>$comparison</td>
                <td>$target</td>
                <td>$parameters</td>
            </tr>
            ";
        }
    ?>
    </table>
</section>
