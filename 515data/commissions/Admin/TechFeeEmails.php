<?php

namespace Commissions\Admin;
use App\Mail\TechFee;
use App\User;
use Commissions\Console;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDO;

class TechFeeEmails extends Console
{
    protected $current_date;

    public function __construct()
    {
        $this->current_date = date("Y-m-d");
    }

    public function first_email() {
        $users = DB::table('billing AS b')
            ->selectRaw('b.userid AS user_id,
                u.fname AS first_name,
                u.email,
                b.billdate,
                b.nochargeuntil')
            ->join('users AS u', 'u.id', '=', 'b.userid')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 'b.userid')
            ->where('u.active', 'Yes')
            ->whereRaw('DATE_SUB(b.nochargeuntil,INTERVAL 5 WEEK) = CURRENT_DATE()')
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = b.userid)')
            ->get();

        $this->log('sending first email');
        foreach($users AS $user) {
            $monday = date('Y-m-d', strtotime('next monday'));
            $body = "<p>Ciao $user->first_name,</p>
                     <br>Questo è un semplice promemoria per ricordarti che 5-15 Global Energy inizierà a detrarre il costo di €10 + IVA, direttamente dalle Commissioni maturate in 5-15, a partire da lunedì prossimo <b>$monday.</b><br>
                    
                     <br><p>Per qualsiasi domanda, non esitare a contattarci:
                     <br><br>Servizi Associati
                     <br>5-15 Global Energy Srl
                     <br><br>Email: Assistenza-Associati@5-15globalenergy.it
                     <br>Telefono: 800 946 706 
                     <br>Via della Gronda, 8 55041 Lido di Camaiore (Lucca) <br></p>
                     
                     <small> Le informazioni contenute in questo messaggio sono di carattere confidenziale e possono essere legalmente riservate. In ottemperanza
                        al Decreto Legislativo 196/03 e al Regolamento UE 2016/679 in materia di protezione dei dati personali, il presente messaggio e gli
                        eventuali allegati sono destinati al solo uso esclusivo del ricevente a cui sono indirizzati. Se Lei non è il destinatario previsto o Le è stato
                        inviato per errore, Le comunichiamo che qualsiasi uso, distribuzione o copia del messaggio è severamente proibita. Se ha ricevuto
                        questo messaggio per errore, La preghiamo di eliminare il messaggio e tutti gli eventuali allegati, senza leggerne il contenuto, e di
                        informare immediatamente il mittente della trasmissione errata.</small>";

            \Commissions\Mail::send(
                    $user->email,
                    "Manca una settimana al pagamento della Quota Mensile Gestionale",
                    $body
                );
        }
        $this->log('done sending email');
    }

    public function second_email() {
        $users = DB::table('billing AS b')
            ->selectRaw('b.userid AS user_id,
                u.fname AS first_name,
                u.email,
                b.billdate,
                b.nochargeuntil')
            ->join('users AS u', 'u.id', '=', 'b.userid')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 'b.userid')
            ->where('u.active', 'Yes')
            ->whereRaw("CURRENT_DATE() = (DATE_SUB(getNextMonthlyBillDate(b.billdate), INTERVAL 7 DAY))")
            ->whereRaw('((b.nochargeuntil < CURRENT_DATE()) OR (DATE_SUB(b.nochargeuntil, INTERVAL 7 DAY) = CURRENT_DATE()))')
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = b.userid)')
            ->get();

        $this->log('sending second email');
        foreach($users as $user) {
            $body = "<p>Ciao $user->first_name,</p> 
            <p>Questo è un promemoria per farti sapere che la Quota Mensile Gestionale è in scadenza il <b>$user->nochargeuntil</b>. 5-15 Global Energy inizierà a detrarre il costo di €10 + IVA, direttamente dai commissioni guadagnate della prossima settimana. 
            Se le commissioni guadagnate non saranno sufficienti a coprire il totale della Quota Mensile Gestionale, ti chiederemo di aggiungere una carta di credito per poter mantenere l’accesso al tuo Portale. Il pagamento via carta verrà trattato come una tantum.
            La Quota Mensile Gestionale dovrà essere saldata entro <b>$user->nochargeuntil</b>, nel caso in cui 5-15 non fosse in grado di addebitarti questo costo, il tuo Portale verrà temporaneamente disattivato e non avrai più accesso alle tue pagine web, alla tua app e al tuo gestionale. 
            Per riattivare il tuo Gestionale, entra nel portale ed aggiungi le informazioni della tua carta di credito o debito. </p>  
             
            <p>Per qualsiasi domanda non esitare a contattarci:
            <br><br>Servizi Associati
            <br>5-15 Global Energy Srl
            <br><br>Email: Assistenza-Associati@5-15globalenergy.it
            <br>Telefono: 800 946 706 
            <br>Via della Gronda, 8 55041 Lido di Camaiore (Lucca) <br></p>
            
             <small> Le informazioni contenute in questo messaggio sono di carattere confidenziale e possono essere legalmente riservate. In ottemperanza
                al Decreto Legislativo 196/03 e al Regolamento UE 2016/679 in materia di protezione dei dati personali, il presente messaggio e gli
                eventuali allegati sono destinati al solo uso esclusivo del ricevente a cui sono indirizzati. Se Lei non è il destinatario previsto o Le è stato
                inviato per errore, Le comunichiamo che qualsiasi uso, distribuzione o copia del messaggio è severamente proibita. Se ha ricevuto
                questo messaggio per errore, La preghiamo di eliminare il messaggio e tutti gli eventuali allegati, senza leggerne il contenuto, e di
                informare immediatamente il mittente della trasmissione errata.</small>";

            \Commissions\Mail::send(
					$user->email,
                    "Promemoria Addebito Quota Gestionale di 5-15",
                    $body
                );
        }
        $this->log('done sending email');

    }
    public function third_email() {
        $users = DB::table('billing AS b')
            ->selectRaw('b.userid AS user_id,
                u.fname AS first_name,
                u.email,
                b.billdate,
                b.nochargeuntil')
            ->join('users AS u', 'u.id', '=', 'b.userid')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 'b.userid')
            ->where('u.active', 'Yes')
            ->whereRaw('CURRENT_DATE() = (DATE_SUB(getNextMonthlyBillDate(b.billdate), INTERVAL 1 DAY))')
            ->whereRaw('((b.nochargeuntil < CURRENT_DATE()) OR (DATE_SUB(b.nochargeuntil, INTERVAL 1 DAY) = CURRENT_DATE()))') //1 day before due date
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = b.userid)')
            ->get();


        $this->log('sending third email');
        foreach($users as $user) {
            $body = '<!DOCTYPE html>
                    <html>
                    <head>
                    </head>
                    <body>
                    <p>Ciao '.$user->first_name.',</p>
                    Ti ricordiamo che la Quota mensile per l’utilizzo del Gestionale di 5-15 è in scadenza domani,il <b>'.$user->nochargeuntil.'</b>. Per mantenere attivo il tuo portale, inserisci qui i dati della tua carta di credito o debito:
                    <a href="https://office.515globalenergy.me/515_profile.cgi">https://office.515globalenergy.me/515_profile.cgi</a>
                    
                    <br><p>Come prima scelta, 5-15 Global Energy continuerà ad addebitarti il costo della quota mensile gestionale di €10 + IVA, dalle tue commissioni; la tua carta di credito verrà utilizzata solo quando ce lo dici tu, ed ogni pagamento con la carta verrà trattato come se fosse una tantum.
                    Nel caso in cui 5-15 Global Energy non ha la possibilità di procedere con il pagamento, l’accesso al tuo Portale verrà temporaneamente disattivato; ciò vuol dire che non avrai più accesso alle tue pagine web, alla tua app e al tuo gestionale.
                    Tuttavia, sarai ancora in grado di accedere al tuo portale e inserire le informazioni della tua carta di credito, per riattivare l\'accesso al tuo portale associato. </p>
                    
                    <p>Per qualsiasi domanda non esitare a contattarci:
                    <br><br>Servizi Associati
                    <br>5-15 Global Energy Srl
                    <br><br>Email: Assistenza-Associati@5-15globalenergy.it
                    <br>Telefono: 800 946 706 
                    <br>Via della Gronda, 8 55041 Lido di Camaiore (Lucca)<br></p>
                    
                    <br><small> Le informazioni contenute in questo messaggio sono di carattere confidenziale e possono essere legalmente riservate. In ottemperanza
                        al Decreto Legislativo 196/03 e al Regolamento UE 2016/679 in materia di protezione dei dati personali, il presente messaggio e gli
                        eventuali allegati sono destinati al solo uso esclusivo del ricevente a cui sono indirizzati. Se Lei non è il destinatario previsto o Le è stato
                        inviato per errore, Le comunichiamo che qualsiasi uso, distribuzione o copia del messaggio è severamente proibita. Se ha ricevuto
                        questo messaggio per errore, La preghiamo di eliminare il messaggio e tutti gli eventuali allegati, senza leggerne il contenuto, e di
                        informare immediatamente il mittente della trasmissione errata.</small>
                    </body>
                </html>';

            \Commissions\Mail::send(
					$user->email,
                    "Pagamento necessario per la Quota del Gestionale di 5-15",
                    $body
                );
        }
        $this->log('done sending email');
    }

    public function fourth_email() {
        $users = DB::table('cm_payments AS p')
            ->selectRaw("p.user_id,
				u.fname AS first_name,
				u.email,
				p.techonology_fee_to_subtract AS tech_fee,
				p.created_date,
				DATE_FORMAT(p.created_date, '%M') AS month_name")
            ->join('users AS u', 'u.id', '=', 'p.user_id')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 'p.user_id')
            ->where('u.active', 'Yes')
            ->whereRaw('DATE(p.created_date) = CURRENT_DATE()')
            ->whereRaw('p.is_processed = 1 AND p.techonology_fee_to_subtract > 0')
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = p.user_id)')
            ->get();


        $this->log('sending fourth email');
        foreach($users as $user) {
			$this->send_fourth_email($user->first_name, $user->month_name, $user->email);
        }

		//running loop for transactions
		$transactions = $this->getTodaysSubsTransactions();
        foreach($transactions as $transactionUser) {
			$this->send_fourth_email($transactionUser->first_name, $transactionUser->month_name, $transactionUser->email);
        }


        $this->log('done sending email');
    }

	private function send_fourth_email($firstName, $monthName, $email) {
		$body = '<!DOCTYPE html>
			<html>
				<head>
			</head>
				<body>
					<p>Ciao '.$firstName.',</p>
					Abbiamo ricevuto il pagamento per la Quota Mensile del Portale, per un totale di €10 + IVA
					Il pagamento per il mese di '.$monthName.' è stato approvato.<br>
					Per vedere e scaricare la tua ricevuta, segui questi passaggi:<br>
					1. Accedi al tuo Portale di 5-15 Global Energy;<br>
					2. Clicca sul “Profilo”;
					<img src="https://nxmcdn.com/images/515/profile.png" alt="profile logo" title="profile logo" width="200" height="300" style="display:block"><br>
					3. Vai a “Ricevute Quota Mensile”;
					<img src="https://nxmcdn.com/images/515/receipts.png" alt="receipts logo" title="receipts logo" width="750" height="150" style="display:block"><br>
					4. Trova il mese interessato e clicca su “Scarica”.
					<img src="https://nxmcdn.com/images/515/download.png" alt="download logo" title="download logo" width="350" height="150" style="display:block"><br>
					
					<p>Per qualsiasi domanda non esitare a contattarci:
					<br><br>Servizi Associati
					<br>5-15 Global Energy Srl
					<br><br>Email: Assistenza-Associati@5-15globalenergy.it
					<br>Telefono: 800 946 706 
					<br>Via della Gronda, 8 55041 Lido di Camaiore (Lucca)<br></p>
					
						<small> Le informazioni contenute in questo messaggio sono di carattere confidenziale e possono essere legalmente riservate. In ottemperanza
						al Decreto Legislativo 196/03 e al Regolamento UE 2016/679 in materia di protezione dei dati personali, il presente messaggio e gli
						eventuali allegati sono destinati al solo uso esclusivo del ricevente a cui sono indirizzati. Se Lei non è il destinatario previsto o Le è stato
						inviato per errore, Le comunichiamo che qualsiasi uso, distribuzione o copia del messaggio è severamente proibita. Se ha ricevuto
						questo messaggio per errore, La preghiamo di eliminare il messaggio e tutti gli eventuali allegati, senza leggerne il contenuto, e di
						informare immediatamente il mittente della trasmissione errata.</small>
				</body>
			</html>';


		\Commissions\Mail::send(
				$email,
				"Conferma di Pagamento del Portale 5-15",
				$body
			);
	}

	private function getTodaysSubsTransactions() {
        $transactions = DB::table('transactions AS t')
			->selectRaw("t.userid AS user_id,
				u.fname AS first_name,
				u.email,
				t.transactiondate AS transaction_date,
				DATE_FORMAT(t.transactiondate, '%M') AS month_name")
            ->join('users AS u', 'u.id', '=', 't.userid')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 't.userid')
            ->where('u.active', 'Yes')
            ->where('t.status', 'Approved')
            ->where('t.type', 'sub')
            ->whereRaw('(t.credited IS NULL OR t.credited = "")')
            ->whereRaw('DATE(t.transactiondate) = CURRENT_DATE()') //transaction date is currentdate
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = t.userid)')
            ->get();
        return $transactions;
	}

    public function fifth_email() {
        $users = DB::table('transactions AS t')
            ->selectRaw('t.userid AS user_id,
            u.fname AS first_name,
            u.email,
            t.transactiondate AS transaction_date,
            b.nochargeuntil,
            DATE_ADD(b.nochargeuntil, INTERVAL 1 DAY) AS after_due_date,
            DATE_FORMAT(b.nochargeuntil, "%M") AS month_name')
            ->join('users AS u', 'u.id', '=', 't.userid')
            ->join('cm_affiliates AS a', 'a.user_id', '=', 't.userid')
            ->join('billing AS b', 'b.userid', '=', 't.userid')
            ->where('u.active', 'No')
            ->where('t.status', 'Declined')
            ->where('t.type', 'sub')
            ->whereRaw('DATE_SUB(DATE(t.transactiondate), INTERVAL 1 DAY) = CURRENT_DATE()') //1 day after the due date
            ->whereRaw('NOT EXISTS(SELECT 1 FROM cm_terminated_associates WHERE user_id = t.userid)')
            ->groupBy('t.userid')
            ->orderBy('t.transactiondate', 'DESC')
            ->get();

        $this->log('sending fifth email');
        foreach($users as $user) {
                $body = '<p>Ciao '.$user->first_name.', </p>
            Sfortunatamente non siamo riusciti ad addebitarti la quota mensile del gestionale di €10 + IVA, a copertura del mese di '.$user->month_name.'.  Di conseguenza, il tuo accesso al portale di 5-15 Global Energy è stato temporaneamente disattivato.<br>
            Ci sono 2 modi per riattivare il tuo portale:<br>
            <br><b>Opzione 1:</b>
            <br>Iscrivi nuove utenze per coprire la quota mensile direttamente dai tuoi guadagni.
            <br>Qui sotto troverai il link per iscrivere i clienti con Italia Gas e Luce, usando il tuo codice
            Associato:
            <br><a href ="https://www.plank.global/plank/attivaonline/IGL/index.php">https://www.plank.global/plank/attivaonline/IGL/index.php</a><br>
            Il tuo Codice Associato è: '.$user->user_id.'<br>
            
            <br><b>Opzione 2:</b>
             
            <br>Accedi al tuo portale di 5-15 Global Energy, inserisci i dati della tua carta di credito o debito, ed effettua il pagamento di €10 + VAT per il mese di '.$user->month_name.'
            
            <p>Per qualsiasi domanda non esitare a contattarci:
            <br><br>Servizi Associati
            <br>5-15 Global Energy Srl
            <br><br>Email: Assistenza-Associati@5-15globalenergy.it
            <br>Telefono: 800 946 706 
            <br>Via della Gronda, 8 55041 Lido di Camaiore (Lucca)<br></p>
            
             <small> Le informazioni contenute in questo messaggio sono di carattere confidenziale e possono essere legalmente riservate. In ottemperanza
                al Decreto Legislativo 196/03 e al Regolamento UE 2016/679 in materia di protezione dei dati personali, il presente messaggio e gli
                eventuali allegati sono destinati al solo uso esclusivo del ricevente a cui sono indirizzati. Se Lei non è il destinatario previsto o Le è stato
                inviato per errore, Le comunichiamo che qualsiasi uso, distribuzione o copia del messaggio è severamente proibita. Se ha ricevuto
                questo messaggio per errore, La preghiamo di eliminare il messaggio e tutti gli eventuali allegati, senza leggerne il contenuto, e di
                informare immediatamente il mittente della trasmissione errata.</small>';

                \Commissions\Mail::send(
					$user->email,
                    "Il tuo Portale è ora Inattivo",
                    $body
                );

        }
        $this->log('done sending email');
    }
}