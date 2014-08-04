<?php
	$trace_arr = isset($ex) ? $ex->getTrace() : array();
?>
<div class="panel panel-danger text-left">
	<div class="panel-heading"><strong>Uncaught Exception</strong></div>
	<div class="panel-body">
		<p>
			<pre><?= $ex->getMessage() ?></pre>
		</p>
		<table class="table table-striped table-bordered table-condensed table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>Function</th>
					<th>Location</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($trace_arr as $idx => $trace) { ?>
				<tr>
					<td>
						<?=$idx?>
					</td>
					<td class="text-left">
						<code>
							<?=isset($trace['class']) ? $trace['class'] : ''?><?=isset($trace['type']) ? $trace['type'] : ''?><?=isset($trace['function']) ? $trace['function'] : ''?>
						</code>
					</td>
					<td>
						<code>
							<?php if (isset($trace['file'])) { ?>
								<?=$trace['file']?> (<?=$trace['line']?>)
							<?php } ?>
						</code>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
