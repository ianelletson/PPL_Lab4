%% Grammar
%% <blck> ::= begin <stmts> end
%% 
%% <stmts> ::= <empty>
          %% | <stmt> <stmts>
%% 
%% <stmt> ::= pass
%%          | declare <name>
%%          | use <name>
%%          | <blck>

%% Statement
stmt([pass|More], More, pass).
stmt([declare,X|More], More, declare(X)) :- not(nonTerminal(X)). 
stmt([use,X|More], More, use(X)) :- not(nonTerminal(X)).
%% stmt(Block, More, P) :- blck(Block, More, P).
stmt(A,P) :- stmt(A, [], P).

%% Statements
stmts([pass|More], More, [pass]).
stmts([pass|More], Other, [pass|P]) :- stmts(More,Other,P).
stmts([use,X|More], More, P) :- stmt([use,X],P).
stmts([use,X|More], Other, MoreP) :- 
    stmt([use,X],P), stmts([More,Other],[P|MoreP]).
%% stmts([Keyword, Var|More], More, P) :- 
%%     stmt([Keyword,Var], P).
%% stmts([Keyword, Var|More], Other, [P|MoreP]) :-
%%     stmt([Keyword,Var],P), stmts([More,Other],MoreP).
%% stmts([begin|Stmts], More) :- stmts(Stmts, [end|More]).
stmts(A,P) :- stmts(A,[],P).

%% Block
blck([begin,end|Tail], Tail).
blck([begin|Stmts], Tail) :- stmts(Stmts, [end|Tail]).
blck(Block) :- blck(Block, []).

%% Legal
legal(L) :- blck(L,[]).

%% Facts
nonTerminal(pass).
nonTerminal(declare).
nonTerminal(use).
nonTerminal(begin).
nonTerminal(end).
