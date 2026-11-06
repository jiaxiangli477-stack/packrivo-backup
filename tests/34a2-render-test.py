import importlib.util
import csv
import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

PATH = Path(__file__).with_name('34a2-render-audit.py')
spec = importlib.util.spec_from_file_location('render_audit_34a2', PATH)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class RenderAuditTest(unittest.TestCase):
    def test_compares_decoded_values_and_reports_home_exception(self):
        page = {'path':'/', 'status':200, 'initial_status':200,
                'title':'Boxes &amp; Gifts', 'meta':'Description', 'h1':'Boxes & Gifts.'}
        row = {'新 title':'Boxes & Gifts', '新 meta':'Description', '新 H1':'Boxes and Gifts'}
        differences = audit.compare_page(page, row)
        self.assertEqual(len(differences), 1)
        self.assertEqual(differences[0]['field'], 'h1')
        self.assertIn('首页', differences[0]['reason'])

    def test_unsafe_redirect_is_a_failure_not_an_empty_pass(self):
        page = {'path':'/x/', 'status':0, 'initial_status':301,
                'title':'', 'meta':'', 'h1':'', 'error':'external redirect blocked'}
        row = {'新 title':'Title','新 meta':'Meta','新 H1':'Heading'}
        self.assertEqual(len(audit.compare_page(page, row)), 3)

    def test_probe_distinguishes_render_override_from_database_mismatch(self):
        page = {'path':'/about/', 'status':200, 'initial_status':200,
                'title':'Old output', 'meta':'Meta', 'h1':'Heading'}
        row = {'新 title':'Target', '新 meta':'Meta', '新 H1':'Heading'}
        probe={'/about/':{'fields':{'_yoast_wpseo_title':[{'meta_value':'Target'}],
                                   '_packrivo_document_title':[]},'content':'<h1>Heading</h1>'}}
        differences=audit.compare_page(page,row,probe)
        self.assertIn('数据库',differences[0]['reason'])
        self.assertIn('覆盖',differences[0]['reason'])

    def test_non_hub_ignores_unused_packrivo_metadata(self):
        page={'path':'/labels/','status':200,'initial_status':200,'title':'Other','meta':'Meta','h1':'Heading'}
        row={'新 title':'Target','新 meta':'Meta','新 H1':'Heading'}
        probe={'/labels/':{'fields':{'_yoast_wpseo_title':[{'meta_value':'Target'}],
                                    '_packrivo_document_title':[{'meta_value':'Unused'}]},'content':''}}
        self.assertIn('覆盖',audit.compare_page(page,row,probe)[0]['reason'])

    def test_redlines_distinguish_self_claim_from_supplier_reference(self):
        result = audit.redlines('MOQ 500. ISO 9001. We are a packaging factory. A supplier factory was visited.')
        self.assertEqual(result['moq_500'], 1)
        self.assertEqual(result['standards'], 1)
        self.assertEqual(result['factory_self_claim'], 1)
        self.assertEqual(audit.redlines('A supplier factory was visited.')['factory_self_claim'], 0)
        self.assertEqual(audit.redlines('Packrivo is not a factory.')['factory_self_claim'], 0)
        self.assertEqual(audit.redlines("Our supplier's factory was visited.")['factory_self_claim'], 0)
        self.assertEqual(audit.redlines('Our supplier factory was visited.')['factory_self_claim'], 0)
        self.assertEqual(audit.redlines('ISO9001 and FSC®')['standards'], 2)
        self.assertEqual(audit.redlines('isomorphic')['standards'], 0)

    def test_cli_checks_all_141_and_reports_a_late_mismatch(self):
        with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\34a2-rehearsal') as temp:
            root=Path(temp); map_path=root/'map.csv'; render_path=root/'render.jsonl'; out=root/'out.json'
            with map_path.open('w',encoding='utf8',newline='') as f:
                writer=csv.writer(f);writer.writerow(['url','新 title','新 meta','新 H1'])
                for i in range(141):writer.writerow([f'https://example.invalid/p{i}/','Title','Meta','Heading'])
            with render_path.open('w',encoding='utf8') as f:
                for i in range(141):
                    page={'path':f'/p{i}/','status':200,'initial_status':200,'title':'Title','meta':'Meta',
                          'h1':'Wrong' if i==140 else 'Heading','html':'<main>Clean</main>'}
                    f.write(json.dumps(page)+'\n')
            result=subprocess.run([sys.executable,str(PATH),str(map_path),str(render_path),'--out',str(out)],capture_output=True,text=True,encoding='utf8',env=dict(os.environ,PYTHONUTF8='1'))
            self.assertEqual(result.returncode,1,result.stdout+result.stderr)
            data=json.loads(out.read_text(encoding='utf8'))
            self.assertEqual(data['checked_fields'],423)
            self.assertEqual(len(data['mismatches']),1)
            self.assertEqual(data['mismatches'][0]['path'],'/p140/')


if __name__ == '__main__':
    unittest.main()
