<?php if (!defined('THINK_PATH')) exit(); /*a:0:{}*/ ?>
<div class="form-group col-xs-12 specification <?php echo (isset($extra_class) && ($extra_class !== '')?$extra_class:''); ?>" id="form_group_<?php echo $name; ?>">
    <label class="col-xs-12" for="<?php echo $name; ?>"><?php echo htmlspecialchars($title); ?></label>
    <div class="col-sm-12">
        <div class="td">
          <div class="spec-tree">
            <table cellspacing="0" cellpadding="0" border="0" class="table table-bordered point-t" id="goods-table">
              <thead>
                <tr>
                  <th style="width:12%;">规格值</th>
                  <th style="width:10%;">销售价 <span class="text-danger">*</span></th>
                  <th style="width:10%;">原价</th>
                  <th style="width:10%;">成本价</th>
                  <th style="width:10%;">库存 <span class="text-danger">*</span></th>
                  <th style="width:10%;">商品货号</th>
                  <th style="width:10%;">条形码</th>
                </tr>
              </thead>
              <tbody id="dataNode">            
                          
              </tbody>
            </table>
          </div>
        </div>

        <?php if(!(empty($tips) || (($tips instanceof \think\Collection || $tips instanceof \think\Paginator ) && $tips->isEmpty()))): ?>
        <div class="help-block"><?php echo $tips; ?></div>
        <?php endif; ?>
    </div>
</div>