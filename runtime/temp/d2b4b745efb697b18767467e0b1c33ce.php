<?php if (!defined('THINK_PATH')) exit(); /*a:0:{}*/ ?>
<div class="form-group col-xs-12 <?php echo (isset($extra_class) && ($extra_class !== '')?$extra_class:''); ?>" id="form_group_<?php echo $name; ?>">
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
                  <th style="width: 10%; display: none;">积分</th>
                  <th style="width: 18%; display: none;">积分+金钱</th>
                </tr>
              </thead>
              <tbody id="dataNode">            
                <tr data-pid="8612">
                  <td>选择一:常温，选择二:微糖</td>
                  <td>
                    <div>
                      <input type="hidden" name="4b5657a5c2[sku_id]" value="8612">
                      <input type="number" value="16.000" class="form-control price" name="4b5657a5c2[price]" required="">
                    </div>
                  </td>
                  <td>
                    <input type="number" value="0.000" class="form-control mkt_price" name="4b5657a5c2[mkt_price]">
                  </td>
                  <td>
                    <input type="number" value="0.000" class="form-control cost_price" name="4b5657a5c2[cost_price]">
                  </td>
                  <td nowrap="">
                    <div>
                      <input type="number" value="99999" class="form-control store" name="4b5657a5c2[store]" required="" min="0">
                    </div>
                  </td>
                  <td>
                    <input type="text" value="S5BBDB02E00912" class="form-control bn" name="4b5657a5c2[bn]">
                  </td>
                  <td>
                    <input type="text" value="" class="form-control barcode" name="4b5657a5c2[barcode]">
                  </td>
                  
                  
               
                </tr>
                          
              </tbody>
            </table>
          </div>
        </div>

        <?php if(!(empty($tips) || (($tips instanceof \think\Collection || $tips instanceof \think\Paginator ) && $tips->isEmpty()))): ?>
        <div class="help-block"><?php echo $tips; ?></div>
        <?php endif; ?>
    </div>
</div>