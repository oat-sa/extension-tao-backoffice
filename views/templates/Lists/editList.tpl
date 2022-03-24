
<form>
    <header class="container-title">
        <input type="text" name="label" value="<?= _dh($label) ?>" data-testid="listNameInput">
    </header>
    <div class="container-content" id="list-elements_<?= $uri ?>">
        <ol data-testid="elements" data-ssr="<?=
                $list['totalCount'] > count($list['elements']) ? 'true' : 'false'
             ?>">
            <?php foreach ($elements as $level => $element): ?>
            <?php $name = "list-element_{$level}_".\tao_helpers_Uri::encode($element->getUri()); ?>
            <li id="list-element_<?= $level ?>">
                <div class='list-element'>
                    <div class='list-element'>
                        <div class='list-element__input-container'>
                            <input type='text' name='<?=$name;?>' value='<?=
                              _dh($element->getLabel()) ;?>' data-testid='elementNameInput'/>
                            <div class='list-element__input-container__uri'>
                                <label for='uri_<?=$name;?>' class='title'>URI</label>
                                <input id='uri_<?=$name;?>' type='text' name='uri_${name}' value="<?=
                                    $name;?>" data-testid='elementUriInput'>
                            </div>
                        </div>
                        <span class='icon-checkbox-crossed list-element-delete-btn' data-testid='deleteElementButton'>
                    </div>
                </div>
            </li>
            <?php endforeach ?>
        </ol>

        <footer class="data-container-footer action-bar  ">
            TODO
            <button class="btn-info small rgt icon-save" data-testid="saveElementButton" title="Save list"></button>
            <div class="add-button-container">
                <button class="btn-info small rgt icon-add" data-testid="addElementButton" title="New element"></button>
                <div class="tooltip-container tooltip-hidden"></div>
            </div>
            <span class="lft edit-uri">
                <input type="checkbox" id="<?= $uri ?>" data-testid="editUriCheckbox">
                <label for="<?= $uri ?>">Edit URI</label>
            </span>
        </footer>
        <input type="hidden" name="uri" value="<?= $list['uri'] ?>">
</form>

