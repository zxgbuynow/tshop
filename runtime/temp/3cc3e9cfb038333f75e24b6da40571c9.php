<?php if (!defined('THINK_PATH')) exit(); /*a:0:{}*/ ?>
<style type="text/css">
  .typelist{
    margin:.5rem;
  }
  .typelist label{
    margin-bottom:.5rem;
  }
</style>
<div class="form-group  col-xs-12 <?php echo (isset($extra_class) && ($extra_class !== '')?$extra_class:''); ?>" id="form_group_<?php echo $name; ?>">
    <label class="col-xs-12" for="<?php echo $name; ?>"><?php echo htmlspecialchars($title); ?></label>
    <div class="col-sm-12">
          <div class="form-group form-inline typelist">
            <label for="" class="col-sm-1 control-label">选择一：</label>
            <div class="col-sm-11 speclist" data-spec-id="19" data-spec-type="text" data-spec-name="选择一">
              
              <label class="checkbox-inline" for="for_19_169">
                <input type="checkbox" title="选择五" id="for_19_169" value="169">
                      <input class="form-control edit-spec-name hide" type="text" name="spec_value[19_169]" value="选择五" maxlength="20">
                      <span class="spec-name">选择五</span>
              </label>

            </div>
          </div>
        
        <?php if(!(empty($tips) || (($tips instanceof \think\Collection || $tips instanceof \think\Paginator ) && $tips->isEmpty()))): ?>
        <div class="help-block"><?php echo $tips; ?></div>
        <?php endif; ?>
</div>

