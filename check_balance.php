<?php $content = file_get_contents("resources/views/dashboard.blade.php"); $if_count = substr_count($content, "@if"); $endif_count = substr_count($content, "@endif"); echo "@if count: $if_count
@endif count: $endif_count
"; if ($if_count !== $endif_count) { echo "UNBALANCED!
"; } else { echo "Balanced
"; } ?>
