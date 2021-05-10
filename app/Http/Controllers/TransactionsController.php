<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionsTag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionsController extends Controller
{
    public function getList($request)
    {
        $user = new User();
        $user_by_token = $user->getUserByToken($request->token);

        if ($user_by_token) {
            $offset = (bool) $request->offset ? $request->offset : 0;
            $limit = 100;
            if ((bool) $request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = (bool) $request->orderBy ? $request->orderBy : 'date';
            $sort_by = (bool) $request->sortBy ? $request->sortBy : 'desc';

            $transactions = Transaction::where('user_id', $user_by_token->id);
            if ($request->date && (bool) $request->date[0] && (bool) $request->date[1]) {
                $initial_date = Carbon::parse($request->date[0])->format(
                    'Y/m/d'
                );
                $final_date = Carbon::parse($request->date[1])->format('Y/m/d');
                $transactions = $transactions->whereBetween('date', [
                    $initial_date,
                    $final_date,
                ]);
            }

            if ($request->tags) {
                $transactions_ids = TransactionsTag::whereIn('tag_id', $request->tags)->select('transaction_id')->get();
                $transactions = $transactions->whereIn('id', $transactions_ids);
            }

            $transactions = $transactions
                ->select('description', 'date', 'value', 'type', 'id as key')
                ->orderBy($order_by, $sort_by)
                ->skip($offset)
                ->take($limit)
                ->get();

            // Get all tags from each transaction
            foreach ($transactions as &$transaction) {
                $tags = TransactionsTag::with('tag')
                    ->where('transaction_id', $transaction->key)
                    ->get();
                $transaction->tags = $tags;
                $transaction->date = Carbon::parse($transaction->date)->format(
                    'Y-m-d'
                );
            }

            $total_count = Transaction::where(
                'user_id',
                $user_by_token->id
            )->count();
        }

        return ['transactions' => $transactions, 'total_count' => $total_count];
    }

    public function checkFields($request, $required)
    {
        $error = false;
        $data_error = [];

        foreach ($required as &$field) {
            $validateField = $request->input($field);

            if (!$validateField) {
                $error = true;

                array_push($data_error, [
                    'field' => $field,
                    'label' => 'This field is required',
                ]);
            }
        }

        return ['error' => $error, 'data_error' => $data_error];
    }

    /**
     * Display a transactions and transactions tags lists paginated.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = $this->getList($request);
        $transactions = $list['transactions'];
        $total_count = $list['total_count'];

        return ['transactions' => $transactions, 'total_count' => $total_count];
    }

    /**
     * Store a newly created transaction and transactions tags and returns
     * transactions list updated based on table limit and offset.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $date = Carbon::parse($request->date)->format('Y/m/d');
            $requiredFields = ['description', 'value', 'type', 'date'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];

            if (!$validateFields['error']) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = (bool) $request->offset ? $request->offset : 0;
                $limit = 100;
                if ((bool) $request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = (bool) $request->orderBy ? $request->orderBy : 'date';
                $sort_by = (bool) $request->sortBy ? $request->sortBy : 'desc';

                if ($user_by_token) {
                    // Add new transaction
                    $new_transaction = new Transaction();
                    $new_transaction->description = $request->description;
                    $new_transaction->value = $request->value;
                    $new_transaction->type = $request->type;
                    $new_transaction->date = $date;
                    $new_transaction->user_id = $user_by_token->id;
                    $new_transaction->save();

                    // Add tags to previously created transaction
                    $tags = $request->tags;
                    if (count($tags) > 0) {
                        foreach ($tags as &$tag) {
                            $new_transition_tag = new TransactionsTag();
                            $new_transition_tag->transaction_id =
                                $new_transaction->id;
                            $new_transition_tag->tag_id = $tag;
                            $new_transition_tag->save();
                        }
                    }

                    // Get new transactions list based on offset and table limit
                    $list = $this->getList($request);
                    $transactions = $list['transactions'];
                    $total_count = $list['total_count'];

                    return [
                        'status' => 201,
                        'transactions' => $transactions,
                        'total_count' => $total_count,
                    ];
                }
            }

            return [
                'status' => 401,
                'transactions' => [],
                'total_count' => 0,
                'error' => $data_error,
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Get the specified transactions and their tags to update.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);

            if ($user_by_token) {
                $transaction = Transaction::where('user_id', $user_by_token->id)
                    ->where('id', $id)
                    ->first();

                // Get all tags from each transaction
                $tags = DB::table('transactions_tags as tt')
                    ->join('tags as t', 't.id', '=', 'tt.tag_id')
                    ->where('tt.transaction_id', $id)
                    ->pluck('t.id');

                $transaction->tags = $tags;

                return ['status' => 200, 'transaction' => $transaction];
            }

            return ['status' => 401, 'transaction' => []];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Update the specified transaction and transactions tags and returns
     * transactions list updated based on table limit and offset.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $date = Carbon::parse($request->date)->format('Y/m/d');
            $requiredFields = ['description', 'value', 'type', 'date'];
            $validateFields = $this->checkFields($request, $requiredFields);
            $data_error = $validateFields['data_error'];

            if (!$validateFields['error']) {
                $user = new User();
                $user_by_token = $user->getUserByToken($request->token);
                $offset = (bool) $request->offset ? $request->offset : 0;
                $limit = 100;
                if ((bool) $request->limit && $request->limit < 100) {
                    $limit = $request->limit;
                }
                $order_by = (bool) $request->orderBy ? $request->orderBy : 'date';
                $sort_by = (bool) $request->sortBy ? $request->sortBy : 'desc';

                if ($user_by_token) {
                    $update_transaction = Transaction::where(
                        'user_id',
                        $user_by_token->id
                    )
                        ->where('id', $id)
                        ->first();

                    if ($update_transaction) {
                        $update_transaction->description =
                            $request->description;
                        $update_transaction->value = $request->value;
                        $update_transaction->date = $date;
                        $update_transaction->save();

                        $tags = $request->tags;

                        $current_tags = TransactionsTag::where(
                            'transaction_id',
                            $id
                        )->pluck('tag_id');

                        $current_tags = $current_tags->toArray();

                        // Add tags to previously updated transaction
                        $add_tags = array_diff($tags, $current_tags);
                        foreach ($add_tags as &$add_tag) {
                            $new_Transaction_Tag = new TransactionsTag();
                            $new_Transaction_Tag->transaction_id = $id;
                            $new_Transaction_Tag->tag_id = $add_tag;
                            $new_Transaction_Tag->save();
                        }
                        // Delete tags to previously updated transaction
                        $delete_tags = array_diff($current_tags, $tags);
                        foreach ($delete_tags as &$delete_tag) {
                            $delete_transaction = TransactionsTag::where(
                                'tag_id',
                                $delete_tag
                            )
                                ->where('transaction_id', $id)
                                ->first();

                            if ($delete_transaction) {
                                $delete_transaction->delete();
                            }
                        }

                        // Get new transactions list based on offset and table limit
                        $list = $this->getList($request);
                        $transactions = $list['transactions'];
                        $total_count = $list['total_count'];

                        return [
                            'transactions' => $transactions,
                            'total_count' => $total_count,
                        ];
                    }
                }
            }

            return ['transactions' => [], 'total_count' => 0];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Remove the specified transactions and their tags.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        try {
            $user = new User();
            $user_by_token = $user->getUserByToken($request->token);
            $offset = (bool) $request->offset ? $request->offset : 0;
            $limit = 100;
            if ((bool) $request->limit && $request->limit < 100) {
                $limit = $request->limit;
            }
            $order_by = (bool) $request->orderBy ? $request->orderBy : 'date';
            $sort_by = (bool) $request->sortBy ? $request->sortBy : 'desc';

            if ($user_by_token) {
                $delete_transaction = Transaction::where(
                    'user_id',
                    $user_by_token->id
                )
                    ->where('id', $id)
                    ->first();

                if ($delete_transaction) {
                    // Get transaction tags list
                    $delete_transaction_tags = TransactionsTag::where(
                        'transaction_id',
                        $id
                    )->get();
                    // Delete all transaction tags before delete transaction
                    foreach ($delete_transaction_tags as &$transaction_tag) {
                        $transaction_tag->delete();
                    }
                    // Delete transaction
                    $delete_transaction->delete();

                    // Get new transactions list based on offset and table limit
                    $list = $this->getList($request);
                    $transactions = $list['transactions'];
                    $total_count = $list['total_count'];

                    return [
                        'transactions' => $transactions,
                        'total_count' => $total_count,
                    ];
                }
            }

            return ['transactions' => [], 'total_count' => 0];
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Display a transactions and transactions tags lists paginated.
     *
     * @return \Illuminate\Http\Response
     */
    public function getChartsInfo(Request $request)
    {
        $user = new User();
        $user_by_token = $user->getUserByToken($request->token);

        if ($user_by_token) {
            $graphTime = $request->graphTime;

            $year = date('Y');
            $month_start = '01';
            $month_end = '12';
            $rows = 12; // Month default

            switch ($graphTime) {
                case '1':
                    // "1" -> Last Year
                    $year = date('Y', strtotime('-1 year'));
                    break;
                case '2':
                    // "2" -> This Year
                    break;
                case '3':
                    // "3" -> Last Month
                    $month_start = date('m', strtotime('-1 month'));
                    $month_end = date('m', strtotime('-1 month'));
                    $rows = date('t');
                    break;
                case '4':
                    // "4" -> This Month
                    $month_start = date('m');
                    $month_end = date('m');
                    $rows = date('t');
                    break;
            }

            $start_date = date($year.'-'.$month_start.'-01');
            $end_date = date($year.'-'.$month_end.'-31');

            $transactions = Transaction::where('user_id', $user_by_token->id)
                ->select('date', 'value', 'type', 'id as key')
                ->whereBetween('date', [$start_date, $end_date])
                ->orderBy('date', 'asc')
                ->get();

            $data = [];

            $initial_data = [
                'income' => 0,
                'expense' => 0,
                'profit' => 0,
            ];

            $income = 0;
            $expense = 0;

            for ($i = 0; $i < $rows; ++$i) {
                array_push($data, (object) $initial_data);
            }

            foreach ($transactions as &$transaction) {
                if ($graphTime === '1' || $graphTime === '2') {
                    $row = Carbon::parse($transaction->date)->format('m');
                    $row = ltrim($row, '0');
                } else {
                    $row = Carbon::parse($transaction->date)->format('d');
                    $row = ltrim($row, '0');
                }

                switch ($transaction->type) {
                    case 1:
                        // 1 - Income
                        $data[$row]->income =
                            $data[$row]->income + $transaction->value;
                        $data[$row]->profit =
                            $data[$row]->profit + $transaction->value;
                        $income = $income + $transaction->value;
                        break;
                    case 2:
                        // 2 - Expense
                        $data[$row]->expense =
                            $data[$row]->expense + $transaction->value;
                        $data[$row]->profit =
                            $data[$row]->profit - $transaction->value;
                        $expense = $expense + $transaction->value;
                        break;
                    case 3:
                        // 3 - Refund
                        $data[$row]->expense =
                            $data[$row]->expense - $transaction->value;
                        $data[$row]->profit =
                            $data[$row]->profit + $transaction->value;
                        $expense = $expense - $transaction->value;
                        break;
                }
            }
        }

        return [
            'transactions' => $transactions,
            'data' => $data,
            'income' => $income,
            'expense' => $expense,
            'start' => $start_date,
            'end' => $end_date,
            'year' => $year,
            'row' => $rows,
        ];
    }
}
