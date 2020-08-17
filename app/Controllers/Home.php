<?php namespace App\Controllers;


use CodeIgniter\HTTP\Files\UploadedFile;

class Home extends BaseController
{
    protected $db;
    protected $request;

    public function __construct()
    {
        $this->db = db_connect();
    }


    private function getUserByAuth()
    {
        var_export($this->request->getHeaders());
        $user = $this->db->table('users')
            ->where('authentication', $this->request->getHeader('authentication')->getValue())
            ->get();
        $userArray = $user->getResultArray();
        if (sizeof($userArray) > 0) {
            return $userArray[0];

        } else {
            return false;
        }
    }

    public
    function register()
    {
        $data = $this->request->getPost();
        $user = $this->db->table('users')
            ->where('email', $data['email'])
            ->get();
        $userArray = $user->getResultArray();
        if (sizeof($userArray) > 0) {
            return $this->response->setBody('User already found')->setStatusCode(201);
        } else {
            $store = $this->db->table('users')
                ->insert([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'authentication' => password_hash(rand(0, 99999999), PASSWORD_BCRYPT),
                    'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                    'userType' => $data['userType'],
                    'foodPreference' => $data['foodPreference'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s"),
                ]);
            if ($this->db->insertID()) {
                $q = $this->db->table('users')->where('id', $this->db->insertID())->get()->getResultArray();
                return $this->response->setBody(json_encode($q[0]))->setStatusCode(200);
            } else {
                return $this->response->setBody('some error occured')->setStatusCode(202);

            }
        }
    }

    public
    function login()
    {
        $data = $this->request->getPost();
        $user = $this->db->table('users')
            ->where('email', $data['email'])
            ->get();
        $userArray = $user->getResultArray();
        if (sizeof($userArray) > 0) {
            if (password_verify($data['password'], $userArray[0]['password'])) {
                return $this->response->setBody(json_encode($userArray[0]))->setStatusCode(200);
            } else {
                return $this->response->setBody('Invalid Password')->setStatusCode(202);
            }
        } else {
            return $this->response->setBody('Users not found')->setStatusCode(201);
        }
    }

    public
    function fetchRestaurantProduct()
    {
        $userId = $this->request->getPost(['userId']);
//        $userId = $this->getUserByAuth();
        if ($userId) {
            $products = $this->db->table('products')->where('userId', $userId)
                ->get();
            $productsArray = $products->getResultArray();
            if (sizeof($productsArray) > 0) {
                return $this->response->setBody(json_encode($productsArray))->setStatusCode(200);
            } else {
                return $this->response->setBody('Products not found')->setStatusCode(201);
            }
        } else {
            return $this->response->setBody('Unauthorized request')->setStatusCode(400);
        }

    }

    public function fetchUserOrders()
    {
        $userId = $this->request->getPost(['userId']);
//        $userId = $this->getUserByAuth();
        if ($userId) {
            $orders = $this->db->table('products')
                ->select('products.*, orders.*')
                ->join('orders', 'orders.productId = products.id')
                ->where('orders.userId', $userId)
                ->get();
            $ordersArray = $orders->getResultArray();
            if (sizeof($ordersArray) > 0) {
                return $this->response->setBody(json_encode($ordersArray))->setStatusCode(200);
            } else {
                return $this->response->setBody('Products not found')->setStatusCode(201);
            }
        } else {
            return $this->response->setBody('Unauthorized request')->setStatusCode(400);
        }

    }

    public
    function fetchRestaurantOrders()
    {
        $userId = $this->request->getPost(['userId']);
//        $userId = $this->getUserByAuth();
        if ($userId) {
            $orders = $this->db->table('products as p')
                ->select('p.*, o.*,u.*')
                ->join('orders as o', 'o.productId = p.id')
                ->join('users as u', 'u.id = o.userId')
                ->where('p.userId', $userId)
                ->get();
            $ordersArray = $orders->getResultArray();
            if (sizeof($ordersArray) > 0) {
                return $this->response->setBody(json_encode($ordersArray))->setStatusCode(200);
            } else {
                return $this->response->setBody('Products not found')->setStatusCode(201);
            }
        } else {
            return $this->response->setBody('Unauthorized request')->setStatusCode(400);
        }
    }

    public function fetchProductById()
    {
        $products = $this->db->table('products')
            ->where('id', $this->request->getPost(['productId']))
            ->get();
        $productsArray = $products->getResultArray();
        if (sizeof($productsArray) > 0) {
            return $this->response->setBody(json_encode($productsArray[0]))->setStatusCode(200);
        } else {
            return $this->response->setBody('Products not found')->setStatusCode(201);
        }
    }

    public
    function fetchAllProduct()
    {
        $products = $this->db->table('products')
            ->where('status', 'active')
            ->get();
        $productsArray = $products->getResultArray();
        if (sizeof($productsArray) > 0) {
            return $this->response->setBody(json_encode($productsArray))->setStatusCode(200);
        } else {
            return $this->response->setBody('Products not found')->setStatusCode(201);
        }
    }

    public function saveProduct()
    {
        $fileNameFinal = [];
        //logic for future case of multiple photo upload
        $total = 1;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $tmpFilePath = $_FILES['photo']['tmp_name'];
            if ($tmpFilePath != "") {
                $newFilePath = "uploads/" . rand(0, 99999) . $_FILES['photo']['name'];
    //            dd($newFilePath);
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $fileNameFinal = 'http://localhost/internshala/backendFoodshala/public/' . $newFilePath;
                }
            }
        }
        


        $store = $this->db->table('products')
            ->insert([
                'userId' => $this->request->getPost(['userId']),
                'pname' => $this->request->getPost(['name']),
                'description' => $this->request->getPost(['description']),
                'amount' => $this->request->getPost(['amount']),
                'foodType' => $this->request->getPost(['foodType']),
                'image' => $fileNameFinal,
                'createdAt' => date("Y-m-d H:i:s"),
                'updatedAt' => date("Y-m-d H:i:s"),
            ]);
        if ($this->db->insertID()) {
            return $this->response->setBody(json_encode($store))->setStatusCode(200);
        } else {
            return $this->response->setBody('some error occured')->setStatusCode(202);

        }
    }

    public function placeOrder()
    {
        $store = $this->db->table('orders')
            ->insert([
                'userId' => $this->request->getPost(['userId']),
//                'userId' => (int)$this->getUserByAuth()['id'],
                'productId' => $this->request->getPost(['productId']),
                'status' => 'success',
                'createdAt' => date("Y-m-d H:i:s"),
                'updatedAt' => date("Y-m-d H:i:s"),
            ]);
        if ($this->db->insertID()) {
            return $this->response->setBody(json_encode($store))->setStatusCode(200);
        } else {
            return $this->response->setBody('some error occured')->setStatusCode(202);

        }
    }
}
