import pandas as pd
from glob import glob

def main(outputxlsx,inputcsvs):
	with pd.ExcelWriter(outputxlsx,engine='xlsxwriter') as writer:
		for f in inputcsvs:
		  pd.read_csv(f).to_excel(writer,index=False,sheet_name=f.split('.')[0].split('/')[-1][:31])

import sys
if __name__=="__main__":
  if len(sys.argv) < 3:
    print('Uso : '+sys.argv[0]+' <outxlsx> <inputcsv1> [<inputcsvn>]')
    exit(1)
  main(sys.argv[1],sys.argv[2:])
  exit(0)
