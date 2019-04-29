</main>
<?php 
if(!isset($contentOnly) || !$contentOnly): 
?>
<footer>
    &copy;&nbsp;<?php echo date('Y'); ?> Oregon State University
    <a class="disclaimer" href="https://oregonstate.edu/official-web-disclaimer" target="_blank">Disclaimer</a>
</footer>
<?php
endif;
?>
</body>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
</script>
</html>