<?php /* Smarty version 2.6.14, created on 2014-01-16 22:33:35
         compiled from phpgacl/edit_objects.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'upper', 'phpgacl/edit_objects.tpl', 46, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/header.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/acl_admin_js.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
  </head>
  <body>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/navigation.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>  
    <form method="post" name="edit_objects" action="edit_objects.php">
      <table cellpadding="2" cellspacing="2" border="2" width="100%">
        <tbody>
          <tr class="pager">
            <td colspan="7">
                <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/pager.tpl", 'smarty_include_vars' => array('pager_data' => $this->_tpl_vars['paging_data'],'link' => "?section_value=".($this->_tpl_vars['section_value'])."&object_type=".($this->_tpl_vars['object_type'])."&")));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
            </td>
          </tr>
          <tr>
            <th width="2%">ID</th>
            <th>Section</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th width="4%">Functions</th>
            <th width="2%"><input type="checkbox" class="checkbox" name="select_all" onClick="checkAll(this)"/></th>
          </tr>
<?php unset($this->_sections['x']);
$this->_sections['x']['name'] = 'x';
$this->_sections['x']['loop'] = is_array($_loop=$this->_tpl_vars['objects']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['x']['show'] = true;
$this->_sections['x']['max'] = $this->_sections['x']['loop'];
$this->_sections['x']['step'] = 1;
$this->_sections['x']['start'] = $this->_sections['x']['step'] > 0 ? 0 : $this->_sections['x']['loop']-1;
if ($this->_sections['x']['show']) {
    $this->_sections['x']['total'] = $this->_sections['x']['loop'];
    if ($this->_sections['x']['total'] == 0)
        $this->_sections['x']['show'] = false;
} else
    $this->_sections['x']['total'] = 0;
if ($this->_sections['x']['show']):

            for ($this->_sections['x']['index'] = $this->_sections['x']['start'], $this->_sections['x']['iteration'] = 1;
                 $this->_sections['x']['iteration'] <= $this->_sections['x']['total'];
                 $this->_sections['x']['index'] += $this->_sections['x']['step'], $this->_sections['x']['iteration']++):
$this->_sections['x']['rownum'] = $this->_sections['x']['iteration'];
$this->_sections['x']['index_prev'] = $this->_sections['x']['index'] - $this->_sections['x']['step'];
$this->_sections['x']['index_next'] = $this->_sections['x']['index'] + $this->_sections['x']['step'];
$this->_sections['x']['first']      = ($this->_sections['x']['iteration'] == 1);
$this->_sections['x']['last']       = ($this->_sections['x']['iteration'] == $this->_sections['x']['total']);
?>
          <tr valign="top" align="center">
            <td>
              <?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>

              <input type="hidden" name="objects[<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
][]" value="<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
">
            </td>
            <td><?php echo $this->_tpl_vars['section_name']; ?>
</td>
            <td><input type="text" size="10" name="objects[<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
][]" value="<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['value']; ?>
"></td>
            <td><input type="text" size="10" name="objects[<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
][]" value="<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['order']; ?>
"></td>
            <td><input type="text" size="40" name="objects[<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
][]" value="<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['name']; ?>
"></td>
            <td>&nbsp;</td>
            <td><input type="checkbox" class="checkbox" name="delete_object[]" value="<?php echo $this->_tpl_vars['objects'][$this->_sections['x']['index']]['id']; ?>
"></td>
          </tr>
<?php endfor; endif; ?>
          <tr class="pager">
            <td colspan="7">
                <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/pager.tpl", 'smarty_include_vars' => array('pager_data' => $this->_tpl_vars['paging_data'],'link' => "?section_value=".($this->_tpl_vars['section_value'])."&object_type=".($this->_tpl_vars['object_type'])."&")));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
            </td>
          </tr>
          <tr class="spacer">
            <td colspan="7"></td>
          </tr>
          <tr align="center">
            <td colspan="7"><b>Add <?php echo ((is_array($_tmp=$this->_tpl_vars['object_type'])) ? $this->_run_mod_handler('upper', true, $_tmp) : smarty_modifier_upper($_tmp)); ?>
s</b></td>
          </tr>
          <tr>
            <th>ID</th>
            <th>Section</th>
            <th>Value</th>
            <th>Order</th>
            <th>Name</th>
            <th>Functions</th>
            <th>&nbsp;</th>
          </tr>
<?php unset($this->_sections['y']);
$this->_sections['y']['name'] = 'y';
$this->_sections['y']['loop'] = is_array($_loop=$this->_tpl_vars['new_objects']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['y']['show'] = true;
$this->_sections['y']['max'] = $this->_sections['y']['loop'];
$this->_sections['y']['step'] = 1;
$this->_sections['y']['start'] = $this->_sections['y']['step'] > 0 ? 0 : $this->_sections['y']['loop']-1;
if ($this->_sections['y']['show']) {
    $this->_sections['y']['total'] = $this->_sections['y']['loop'];
    if ($this->_sections['y']['total'] == 0)
        $this->_sections['y']['show'] = false;
} else
    $this->_sections['y']['total'] = 0;
if ($this->_sections['y']['show']):

            for ($this->_sections['y']['index'] = $this->_sections['y']['start'], $this->_sections['y']['iteration'] = 1;
                 $this->_sections['y']['iteration'] <= $this->_sections['y']['total'];
                 $this->_sections['y']['index'] += $this->_sections['y']['step'], $this->_sections['y']['iteration']++):
$this->_sections['y']['rownum'] = $this->_sections['y']['iteration'];
$this->_sections['y']['index_prev'] = $this->_sections['y']['index'] - $this->_sections['y']['step'];
$this->_sections['y']['index_next'] = $this->_sections['y']['index'] + $this->_sections['y']['step'];
$this->_sections['y']['first']      = ($this->_sections['y']['iteration'] == 1);
$this->_sections['y']['last']       = ($this->_sections['y']['iteration'] == $this->_sections['y']['total']);
?>
          <tr valign="top" align="center">
            <td>N/A</td>
            <td><?php echo $this->_tpl_vars['section_name']; ?>
</td>
            <td><input type="text" size="10" name="new_objects[<?php echo $this->_tpl_vars['new_objects'][$this->_sections['y']['index']]['id']; ?>
][]" value=""></td>
            <td><input type="text" size="10" name="new_objects[<?php echo $this->_tpl_vars['new_objects'][$this->_sections['y']['index']]['id']; ?>
][]" value=""></td>
            <td><input type="text" size="40" name="new_objects[<?php echo $this->_tpl_vars['new_objects'][$this->_sections['y']['index']]['id']; ?>
][]" value=""></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
<?php endfor; endif; ?>
          <tr class="controls" align="center">
            <td colspan="5">
              <input type="submit" class="button" name="action" value="Submit"> <input type="reset" class="button" value="Reset"><br>
            </td>
            <td colspan="2">
              <input type="submit" class="button" name="action" value="Delete">
            </td>
          </tr>
        </tbody>
      </table>
    <input type="hidden" name="section_value" value="<?php echo $this->_tpl_vars['section_value']; ?>
">
    <input type="hidden" name="object_type" value="<?php echo $this->_tpl_vars['object_type']; ?>
">
    <input type="hidden" name="return_page" value="<?php echo $this->_tpl_vars['return_page']; ?>
">
  </form>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "phpgacl/footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>