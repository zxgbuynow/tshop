<?php if (!defined('THINK_PATH')) exit(); /*a:0:{}*/ ?>
<style type="text/css">
  .typelist{
    margin:.5rem;
  }
  .typelist label{
    margin-bottom:.5rem;
  }
</style>
<div class="form-group col-xs-12 <?php echo (isset($extra_class) && ($extra_class !== '')?$extra_class:''); ?>" id="form_group_<?php echo $name; ?>">
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
            <label class="checkbox-inline" for="for_19_170">
              <input type="checkbox" title="选择六" id="for_19_170" value="170">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[19_170]" value="选择六" maxlength="20">
                    <span class="spec-name">选择六</span>
            </label>
            <label class="checkbox-inline" for="for_19_171">
              <input type="checkbox" title="选择七" id="for_19_171" value="171">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[19_171]" value="选择七" maxlength="20">
                    <span class="spec-name">选择七</span>
            </label>
                <label class="checkbox-inline" for="for_19_172">
              <input type="checkbox" title="选择八" id="for_19_172" value="172">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[19_172]" value="选择八" maxlength="20">
                    <span class="spec-name">选择八</span>
            </label>
            <label class="checkbox-inline" for="for_19_173">
              <input type="checkbox" title="选择九" id="for_19_173" value="173">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[19_173]" value="选择九" maxlength="20">
                    <span class="spec-name">选择九</span>
            </label>
          </div>
        </div>
    
        <div class="form-group form-inline typelist">
          <label for="" class="col-sm-1 control-label">选择二：</label>
                <label class="checkbox-inline" for="for_39_190">
              <input type="checkbox" title="选择五" id="for_39_190" value="190">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[39_190]" value="选择五" maxlength="20">
                    <span class="spec-name">选择五</span>
            </label>
                <label class="checkbox-inline" for="for_39_191">
              <input type="checkbox" title="选择六" id="for_39_191" value="191">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[39_191]" value="选择六" maxlength="20">
                    <span class="spec-name">选择六</span>
            </label>
                <label class="checkbox-inline" for="for_39_192">
              <input type="checkbox" title="选择七" id="for_39_192" value="192">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[39_192]" value="选择七" maxlength="20">
                    <span class="spec-name">选择七</span>
            </label>
                <label class="checkbox-inline" for="for_39_193">
              <input type="checkbox" title="选择八" id="for_39_193" value="193">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[39_193]" value="选择八" maxlength="20">
                    <span class="spec-name">选择八</span>
            </label>
                <label class="checkbox-inline" for="for_39_194">
              <input type="checkbox" title="选择九" id="for_39_194" value="194">
                    <input class="form-control edit-spec-name hide" type="text" name="spec_value[39_194]" value="选择九" maxlength="20">
                    <span class="spec-name">选择九</span>
            </label>
              </div>
        </div>
        <?php if(!(empty($tips) || (($tips instanceof \think\Collection || $tips instanceof \think\Paginator ) && $tips->isEmpty()))): ?>
        <div class="help-block"><?php echo $tips; ?></div>
        <?php endif; ?>
</div>

