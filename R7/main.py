#-------------------------------------------------------------------------------
# Name:        R-7 Sputnik Admin generator
# Purpose:
#
# Author:      VOOV
#
# Created:     01.02.2011
# Copyright:   (c) VOOV 2011
# Licence:     <your licence>
#-------------------------------------------------------------------------------
#!/usr/bin/env python

import templite
import sys

modules = []
def gen_module(mod_str):
    print(modules)
    print(mod_str)

def main():
    print("R-7")
    num_of_module = int(input("Mennyi modul? "))
    for count in range(0, num_of_module):
        module_text = input("Module meghatározás")
        modules.append(module_text)

    for module in modules:
        gen_module(module)

if __name__ == '__main__':
    main()
