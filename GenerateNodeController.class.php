<?php
namespace Task\Controller;

use Think\Controller;

// GenerateNode
class GenerateNodeController extends Controller
{
    /**
     * @abstract 生成模块节点，并存入数据库. 
     *           注意：1. title,js_page,js_icon 等等需要自己加 2. controller 里有的方法和都会被添加入adm_node表，添加完后需要筛选下 
     *               3. inherentsFunctions可以添加排除的方法 4. 父类的方法已经被排除
     * @param string $moduleName 模块名
     * @author chenyihao <email@email.com>
     */
    public function index()
    {
        exit('呵呵');
        $moduleName=ucfirst($_GET['moduleName']);
        $modelAdmNode=M('AdmNode');

        if (empty($moduleName)) {
            return print('Please fill in the moduleName');
        }
        
        $modulePath = APP_PATH . '/' . $moduleName . '/Controller/';  //控制器路径
        if (!is_dir($modulePath)) {
            return print('The current module folder does not exist');
        }

        // start SQL ..leve 1
        $currLevel1 = $modelAdmNode->where(['name'=>$moduleName,'status'=>1,'level'=>1])->find();
        if (empty($currLevel1)) {
            # 新增
            $resultCurrLevel1 = $modelAdmNode->add(['name'=>$moduleName,'status'=>1,'level'=>1,'pid'=>0,'group_id'=>0]);
            if (false === $resultCurrLevel1 ) {
                return print('update fail');
            }
            $level1Id=$resultCurrLevel1;
        }else{
            # update
            $level1Id=$currLevel1['id'];
        }

        //排除部分方法
        $inherentsFunctions = ['_before_index','_after_index','_initialize','__construct','getActionName','isAjax','display','show','fetch',
                'buildHtml','assign','__set','get','__get','__isset','__call','error','success','ajaxReturn','redirect','__destruct','_empty',
                '_field','_order','_join','_where','_list'];
        
        $modulePath .= '/*.class.php';
        foreach (glob($modulePath) as $file) {
            if (is_dir($file)) {
                continue;
            } else {
                $nowCtrl=basename($file, C('DEFAULT_C_LAYER').'.class.php');
                $nowClassname="{$moduleName}\\Controller\\{$nowCtrl}Controller";
                
                //start SQL level 2
                $currLevel2 = $modelAdmNode->where(['name'=>$nowCtrl,'status'=>1,'level'=>2,'pid'=>$level1Id])->find();
                if (empty($currLevel2)) {
                    # 新增
                    $resultCurrLevel2 = $modelAdmNode->add(['name'=>$nowCtrl,'status'=>1,'level'=>2,'pid'=>$level1Id,'group_id'=>0]);
                    if (false === $resultCurrLevel2 ) {
                        return print('update fail');
                    }
                    $level2Id=$resultCurrLevel2;
                }else{
                    # update
                    $level2Id=$currLevel2['id'];
                }

                $itemCtrl = new \ReflectionClass('\\'.$nowClassname);
                $itemMethods = $itemCtrl->getMethods();
                // var_dump($itemMethods);exit;

                foreach ($itemMethods as $key => $value) {
                    if($value->class==$nowClassname and !in_array($value->name,$inherentsFunctions)){
                        //start SQL level 3
                        $currLevel3 = $modelAdmNode->where(['name'=>$value->name,'status'=>1,'pid'=>$level2Id])->find();
                        if (empty($currLevel3)) {
                            # 新增
                            $resultCurrLevel3 = $modelAdmNode->add(['name'=>$value->name,'status'=>1,'level'=>3,'pid'=>$level2Id,'group_id'=>0]);
                            if (false === $resultCurrLevel3 ) {
                                return print('update fail');
                            }
                            $level3Id=$resultCurrLevel3;
                        }else{
                            # update
                            $level3Id=$currLevel3['id'];
                        }
                    }
                }
            }
        }

        return print('Node is added successfully');
    }
}
