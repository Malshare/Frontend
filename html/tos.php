<?php
// Include i18n for translation support
require_once __DIR__ . '/include/i18n.php';

// Soft 404 page — returns HTTP 200 but indicates a not-found resource to users
http_response_code(200);
header('X-Soft-404: 1');
header('X-Robots-Tag: noindex, follow');

// Include standard site header/footer for consistent look
if (file_exists(dirname(__FILE__) . '/header.php')) {
    include dirname(__FILE__) . '/header.php';
}
if (file_exists(dirname(__FILE__) . '/nav.php')) {
    include dirname(__FILE__) . '/nav.php';
}
?>
<!DOCTYPE html>
<div class="container" style="margin-top: 30px;">

    <h2><?php echo h('tos.title'); ?></h2>
    <h3><?php echo h('tos_content.section_1_title'); ?></h3>
    <p><?php echo t('tos_content.section_1_body'); ?></p>

    <h3><?php echo h('tos_content.section_2_title'); ?></h3>
    <ol type="a">
        <li><?php echo h('tos_content.section_2_intro'); ?>
            <ol type="i">
                <li><?php echo h('tos_content.section_2_item_1'); ?></li>
                <li><?php echo h('tos_content.section_2_item_2'); ?></li>
                <li><?php echo h('tos_content.section_2_item_3'); ?></li>
                <li><?php echo h('tos_content.section_2_item_4'); ?></li>
            </ol>
        </li>
        <li><?php echo h('tos_content.section_2_body_2'); ?></li>
    </ol>

    <h3><?php echo h('tos_content.section_3_title'); ?></h3>
    <ol type="a">
        <li><?php echo h('tos_content.section_3_item_1'); ?></li>
        <li><?php echo h('tos_content.section_3_item_2'); ?></li>
        <li><?php echo h('tos_content.section_3_item_3'); ?></li>
    </ol>

    <h3><?php echo h('tos_content.section_4_title'); ?></h3>
    <p><?php echo h('tos_content.section_4_body'); ?></p>

    <h3><?php echo h('tos_content.section_5_title'); ?></h3>
    <p><?php echo h('tos_content.section_5_body'); ?></p>

    <h3><?php echo h('tos_content.section_6_title'); ?></h3>
    <p><?php echo h('tos_content.section_6_body'); ?></p>

    <h3><?php echo h('tos_content.section_7_title'); ?></h3>
    <p><?php echo h('tos_content.section_7_body'); ?></p>

    <h3><?php echo h('tos_content.section_8_title'); ?></h3>
    <p><?php echo h('tos_content.section_8_body'); ?></p>

</div>
</div>

</div>

<?php
if (file_exists(dirname(__FILE__) . '/footer.php')) {
    include dirname(__FILE__) . '/footer.php';
}

?>



<?php
include_once('footer.php');
?>

</body>
</html>
